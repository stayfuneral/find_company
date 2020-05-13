<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Найти компанию по ИНН");

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Page\Asset;

$user = $GLOBALS['USER']->GetID();
$asset = Asset::getInstance();

$js = '/crm/find/assets/script.js?' . time();
$css = '<link rel="stylesheet" href="/crm/find/assets/style.css?' . time() .'">';

$uiExtensions = ['ui.buttons', 'ui.forms', 'ui.alerts', 'ui.vue'];

foreach($uiExtensions as $extension) {
    Extension::load($extension);
}

$asset->addJs($js);
$asset->addString($css);

?>
<div id="app">

    <template>
    
        <h2>{{title}}</h2>
        <div class="inputArea">
            <input @change="setInn('inn')" id="inn" type="text" class="inn ui-ctl-element ui-ctl-w25">
        </div>
        <button @click="findCompany" id="find" class="ui-btn ui-btn-primary">Найти</button> 

    </template>

    <template v-if="resultCompany.result === 'found'">
        <div class="alert">
            <div id="result" v-for="company in resultCompany.companies">
                <h4 class="ui-alert-message">Компания: {{company.title}}</h4>
                <p class="ui-alert-message">Менеджер: {{company.manager}}<br>
                ИНН: {{company.inn}}</p>                
                
                <div v-for="deal in company.deals">
                    <h4 class="ui-alert-message">Сделка: {{deal.title}}</h4>
                    <p class="ui-alert-message">Стадия: {{deal.deal_stage}}<br>
                    Воронка: {{deal.deal_category}}</p>
                </div>
            </div>        
        </div>
        

    </template>

    <template v-if="resultCompany.result === 'error'">
        <div class="ui-alert ui-alert-danger">
            <div id="result">
                <h3 class="ui-alert-message">{{resultCompany.error_description}}</h3>
            </div>
        </div>
    </template>

    <template v-if="resultCompany.result === 'empty'">
    
        <div class="ui-alert ui-alert-success">
            <div id="result">
                <h3 class="ui-alert-message">Компания не найдена</h3>
                <p class="p">
                <label>
                    <input v-model="createTask" type="checkbox"> Создать задачу
                </label>
                    

                    <div v-if="createTask">
                        <div class="inputArea">
                            <p>ИНН: {{findData.inn}}</p>
                        </div>
                        <div class="inputArea">
                        <input type="hidden" :value="findData.inn">
                            <p>Воронка</p>
                            <select v-model="deal_category" class="ui-ctl-element" id="dealCategory">
                                <option v-for="(category, id) in dealCategories" :value="id">{{category}}</option>
                            </select>
                        
                        </div>
                        <button @click="createNewTask(<?=$user;?>)" class="ui-btn ui-btn-success ui-btn-icon-add" id="createTask">Создать</button>
                        <div v-if="newTaskId > 0">
                            <p class="ui-alert-message">Задача <a :href="'/company/personal/user/<?=$user;?>/tasks/task/view/' + newTaskId +'/'">#{{newTaskId}}</a> создана!</p>
                        </div>
                    </div>
                </p>
            </div>
        </div>
    
    </template>

</div>

<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>