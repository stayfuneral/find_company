window.onload = function() {

    BX.Vue.create({
        el: '#app',
        data: {
            title: 'Введите ИНН',
            findData: {},
            promises: {
                findCompanyPromise: null
            },
            resultCompany: [],
            createTask: false,
            inn: '',
            deal_category: '',
            dealCategories: {},
            url: new URL(window.location.href),
            webhook: 'https://#your_portal#.bitrix24.ru/rest/#user_id#/#webhook_token#/',
            responsibleId: 1,
            newTaskId: 0
        },
        methods: {
            setInn(inn) {
                if(!inn) return false;
                let INN = document.getElementById(inn);
                this.findData[INN.id] = INN.value;
            },
            async getDealCategories() {
                const request = await fetch(this.webhook + 'task.item.userfield.getlist', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        filter: {
                            FIELD_NAME: 'UF_DEAL_CATEGORY',
                            USER_TYPE_ID: 'enumeration'
                        }
                    })
                });
                let result = await request.json();
                
                for(let i = 0; i < result.result.length; i++) {

                    if(result.result[i].FIELD_NAME === 'UF_DEAL_CATEGORY') {
                        let cats = result.result[i].LIST;
                        cats.forEach(item => {
                            this.dealCategories[item.ID] = item.VALUE
                        })
                    }

                }
            },
            findCompany: async function() {
                this.findData.request_type = 'findCompany';

                let request = await fetch('ajax/find.php', {
                    method: 'POST',
                    body: JSON.stringify(this.findData)
                });

                this.resultCompany = await request.json();         
            },
            enableElement(id) {
                let element = document.getElementById(id);
                element.removeAttribute('disabled');
            },
            disableElement(id) {
                let element = document.getElementById(id);
                element.setAttribute('disabled', 'disabled')
            },
            async createNewTask(userId) {

                this.disableElement('dealCategory');
                this.disableElement('createTask');

                let taskFields = {
                    fields: {
                        TITLE: 'Добавление компаний в Битрикс по ИНН',
                        DESCRIPTION_IN_BBCODE: 'Y',
                        CREATED_BY: userId,
                        RESPONSIBLE_ID: this.responsibleId,
                        GROUP_ID: 38,
                        FORKED_BY_TEMPLATE_ID: 41,
                        DESCRIPTION: `Коллеги, прошу добавить следующие организации.
ИНН: `+ this.findData.inn +`
Воронка продаж: ` + this.dealCategories[this.dealCategory]
                    }
                };
                console.log(taskFields)
                const response = await fetch(this.webhook + 'tasks.task.add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(taskFields)
                });
                let result = await response.json();

                if(result.result.task) {
                    
                    this.newTaskId = result.result.task.id;
                    this.enableElement('dealCategory');
                    this.enableElement('createTask');

                }
            }        
        },
        created: function() {
            this.getDealCategories();
        }
    });

}


