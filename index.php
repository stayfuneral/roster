<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.alerts");
CJSCore::Init(Array("viewer"));
CModule::IncludeModule('iblock');
if (isset($_GET['DOCUMENT_ID']) && !empty($_GET['DOCUMENT_ID'])) {
    $docID = intval(htmlspecialchars($_GET['DOCUMENT_ID']));
    $IBLOCK_ID = 43;

    //Проверка доступа к документу
    
    $rsUser = CUser::GetByID($GLOBALS['USER']->GetID());
    $arUser = $rsUser->Fetch();
    if (count($arUser['UF_DEPARTMENT']) == 1) {
        $dep = $arUser['UF_DEPARTMENT'][0];
    }
    $userGroups = CUser::GetUserGroup($GLOBALS['USER']->GetID());
    $groupRights = [];
    $access = false;
    $Rights = new CIBlockElementRights($IBLOCK_ID, $docID);
    $arRights = [];
    $rights = $Rights->GetRights();
    foreach ($rights as $right) {
        if ($right['TASK_ID'] == 107) {
            if (stripos($right['GROUP_CODE'], 'IU') !== false || stripos($right['GROUP_CODE'], 'U') !== false) {
                $groupRights['USERS'][] = preg_replace('[\D]', '', $right['GROUP_CODE']);

            }

            if ((stripos($right['GROUP_CODE'], 'D') === true) && (stripos($right['GROUP_CODE'], 'DR') === false)) {
                $groupRights['DEPARTMENTS'][] = str_replace('D', '', $right['GROUP_CODE']);
            }
            if (stripos($right['GROUP_CODE'], 'DR') !== false) {
                $groupRights['DEPARTMENTS_RECURSIVE'][] = str_replace('DR', '', $right['GROUP_CODE']);
            }

            if ($right['GROUP_CODE'] === 'DR1' or in_array(1, $userGroups) or in_array(25, $userGroups) or in_array($GLOBALS['USER']->GetID(), $groupRights['USERS']) or in_array($groupRights['DEPARTMENTS'], $dep) or in_array($groupRights['DEPARTMENTS_RECURSIVE'], $dep)) {
                $access = true;
            }
        }
    }

    // Получение данных элемента списка
    
    $arSelect = ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_TYPE', 'PROPERTY_PUBLIC_DATE', 'PROPERTY_NUMBER', 'PROPERTY_SRC'];
    $arFilter = ['IBLOCK_ID' => $IBLOCK_ID, 'ID' => $docID];
    $items = [];
    $arFiles = [];
    $res = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
    while ($list = $res->GetNextElement(true, false)) {
        $items[] = $list->GetFields();
        $item = $list->GetFields();
        $files = CFile::GetFileArray($item['PROPERTY_SRC_VALUE']);
        $pathinfo = pathinfo($files['SRC']);
        $filename = explode('/', $files['SRC']);
        $arFiles[] = [
            'NAME' => $filename[4],
            'SRC' => $files['SRC'],
            'EXTENSION' => $pathinfo['extension']
        ];
    } ?>
    <p>
        <a href="/services/lists/43/view/0/" class="ui-btn ui-btn-light-border">Вернуться к списку</a>
    </p>
    <?php if ($access === false) {
        $APPLICATION->SetTitle('Доступ запрещён');?>
        <div class="ui-alert ui-alert-danger ui-alert-text-center">
            <h3 class="ui-alert-message"><?=$arUser['NAME']?>, у вас недостаточно прав для просмотра данного документа</h3>
        </div>
    <?php } else {
        $title = $items[0]['PROPERTY_TYPE_VALUE'] . ' №' . $items[0]['PROPERTY_NUMBER_VALUE'] . ' «' . $items[0]['NAME'] . '» от ' . $items[0]['PROPERTY_PUBLIC_DATE_VALUE'];
    
    $APPLICATION->SetTitle($title);?>

    <p><strong>Тип документа:</strong> <?= $items[0]['PROPERTY_TYPE_VALUE'] ?></p>
    <p><strong>Номер документа:</strong> <?= $items[0]['PROPERTY_NUMBER_VALUE'] ?></p>
    <p><strong>Дата публикации документа:</strong> <?= $items[0]['PROPERTY_PUBLIC_DATE_VALUE'] ?></p>
    <div id="files"></div>
<?php foreach ($arFiles as $File) {?>
            <!-- <p><button class="ui-btn ui-btn-primary"><?=$File['NAME']?></button></p> -->
    <?php }
        }
}?>
    
<script>
    BX.ready(function() {
        let arFiles = <?=CUtil::PhpToJSObject($arFiles)?>;
        let buttons = {};
        arFiles.forEach(function(item, i) {
            buttons = {};
            buttons[i] = BX.create('a', {
                attrs: {
                    className: 'ui-btn ui-btn-primary'
                },
                props: {
                    href: item.SRC,
                    target: '_blank'
                },
                text: item.NAME
            });
            BX.append(BX.create('p', {
                children: [buttons[i]]
            }), BX('files'));
        });

        
    });
</script>            
<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
