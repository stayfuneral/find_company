<?php 

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";



use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Crm\Category\DealCategory;

Loader::includeModule('crm');

$dealCategories = [];
$rawDealCategories = DealCategory::getAll();
                
foreach($rawDealCategories as $cat) {
    $dealCategories[$cat['ID']] = $cat['NAME'];
}

$innUserField = 'UF_CRM_1530861987';

$webhook = 'https://#your_portal#.bitrix24.ru/rest/#user_id#/#webhook_token#/';

$request = Context::getCurrent()->getRequest();

if(!empty($request) && $request->isPost() !== false) {

    $inputs = Json::decode($request->getInput());
    $inputs = (object)$inputs;

    $requestType = $inputs->request_type;
    $inn = $inputs->inn;

    $response = [];
    $http = new HttpClient;

    switch($requestType) {
        case 'findCompany':

            ;
            $params = [
                'order' => ['ID' => 'DESC'],
                'filter' => [$innUserField => $inn],
                'select' => ['ID', 'TITLE', $innUserField, 'ASSIGNED_BY_ID']
            ];

            $companies = $http->post($webhook . 'crm.company.list', $params);
            $companies = Json::decode($companies);

            if(!empty($companies['result'])) {
                $response['result'] = 'find';
                
                

                if(count($companies['result']) > 1) {

                    

                } else {

                    $company = $companies['result'][0];
                    $manager = CUser::GetByID($company['ASSIGNED_BY_ID'])->Fetch();

                    $comp = [
                        'id' => $company['ID'],
                        'title' => $company['TITLE'],
                        'inn' => $inn,
                        'manager' => $manager['NAME'] . ' ' . $manager['LAST_NAME']
                    ];

                    $arDealParams = [
                        'order' => ['ID' => 'DESC'],
                        'filter' => ['COMPANY_ID' => $companies['result'][0]['ID']],
                        'select' => ['ID', 'TITLE', 'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID']
                    ];

                    $deals = $http->post($webhook . 'crm.deal.list', $arDealParams);
                    $deals = Json::decode($deals);

                    if(!empty($deals['result'])) {

                        foreach($deals['result'] as $deal) {

                            $dealId = $deal['ID'];
                            $stageId = $deal['STAGE_ID'];
                            $categoryId = $deal['CATEGORY_ID'];

                            $dealStages = CCrmStatus::GetStatusList('DEAL_STAGE_' . $categoryId);

                            if($deal['ASSIGNED_BY_ID'] != $company['ASSIGNED_BY_ID']) {
                                $dealManager = CUser::GetByID($deal['ASSIGNED_BY_ID'])->Fetch();
                            }

                            $comp['deals'][] = [
                                'id' => $dealId,
                                'title' => $deal['TITLE'],
                                'deal_stage' => $dealId[$stageId],
                                'deal_category' => $dealCategories[$categoryId]
                            ];

                        }

                    }

                    $response['companies'][] = $comp;

                }

            } else if(!empty($companies['error'])) {

                $response['result'] = 'error';
                $response['error_description'] = $companies['error_description'];

            }

            break;
        case 'createTask':
            break;
    }



    echo Json::encode($response, JSON_UNESCAPED_UNICODE);


}

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php";