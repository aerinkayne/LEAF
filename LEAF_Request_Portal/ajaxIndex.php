<?php
/*
 * As a work of the United States government, this project is in the public domain within the United States.
 */

/*
    Index for everything
    Date Created: September 11, 2007

*/

error_reporting(E_ALL & ~E_NOTICE);

include '../libs/smarty/Smarty.class.php';
include 'Login.php';
include 'db_mysql.php';
include 'db_config.php';

// Include XSSHelpers
if (!class_exists('XSSHelpers'))
{
    include_once dirname(__FILE__) . '/../libs/php-commons/XSSHelpers.php';
}

$db_config = new DB_Config();
$config = new Config();

$db = new DB($db_config->dbHost, $db_config->dbUser, $db_config->dbPass, $db_config->dbName);
$db_phonebook = new DB($config->phonedbHost, $config->phonedbUser, $config->phonedbPass, $config->phonedbName);
unset($db_config);

function customTemplate($tpl)
{
    return file_exists("./templates/custom_override/{$tpl}") ? "custom_override/{$tpl}" : $tpl;
}

$login = new Login($db_phonebook, $db);

$login->loginUser();
if ($login)
{
}

$action = isset($_GET['a']) ? $_GET['a'] : '';

$settings = $db->query_kv('SELECT * FROM settings', 'setting', 'data');
if (isset($settings['timeZone']))
{
    date_default_timezone_set($settings['timeZone']);
}

switch ($action) {
    case 'newform':
        require 'form.php';
        $form = new Form($db, $login);
        $recordID = $form->newForm($_SESSION['userID']);
        if (is_numeric($recordID))
        {
            header('Location: index.php?a=view&recordID=' . $recordID);
            exit();
        }
        else
        {
            echo $recordID;
        }

        break;
    case 'getindicator':
        require 'form.php';
        $form = new Form($db, $login);
        if (is_numeric($_GET['indicatorID']))
        {
            $t_form = new Smarty;

            $indicatorID = (int)$_GET['indicatorID'];
            $series = XSSHelpers::xscrub($_GET['series']);
            $recordID = (int)$_GET['recordID'];

            $indicator = $form->getIndicator($indicatorID, $series, $recordID);
            $recordInfo = $form->getRecordInfo($recordID);
            if ($indicator[$_GET['indicatorID']]['isWritable'] == 1)
            {
                $t_form->left_delimiter = '<!--{';
                $t_form->right_delimiter = '}-->';
                $t_form->assign('recordID', $recordID);
                $t_form->assign('series', $series);
                $t_form->assign('serviceID', (int)$recordInfo['serviceID']);
                $t_form->assign('recorder', XSSHelpers::sanitizeHTML($_SESSION['name']));
                $t_form->assign('CSRFToken', $_SESSION['CSRFToken']);
                $t_form->assign('form', $indicator);
                $t_form->assign('orgchartPath', Config::$orgchartPath);
                $t_form->assign('orgchartImportTag', Config::$orgchartImportTags[0]);
                $t_form->assign('subindicatorsTemplate', customTemplate('subindicators.tpl'));
                $t_form->display(customTemplate('ajaxForm.tpl'));
            }
            else
            {
                echo '<img src="../libs/dynicons/?img=emblem-readonly.svg&amp;w=96" alt="error" style="float: left" /><div style="font: 36px verdana">This field is currently read-only OR the field is not associated with any forms on this request.</div>';
            }
        }

        break;
    case 'getprintindicator':
        require 'form.php';
        $form = new Form($db, $login);
        $indicatorID = (int)$_GET['indicatorID'];
        $series = XSSHelpers::xscrub($_GET['series']);
        $recordID = (int)$_GET['recordID'];

        if (is_numeric($indicatorID))
        {
            $t_form = new Smarty;
            $t_form->left_delimiter = '<!--{';
            $t_form->right_delimiter = '}-->';

            if (is_numeric($series))
            {
                $t_form->assign('recordID', $recordID);
                $t_form->assign('series', $series);
                $t_form->assign('recorder', XSSHelpers::sanitizeHTML($_SESSION['name']));
                $indicator = $form->getIndicator($indicatorID, $series, $recordID);
                $t_form->assign('indicator', $indicator[$indicatorID]);
                $t_form->assign('orgchartPath', Config::$orgchartPath);
                $t_form->display('print_subindicators_ajax.tpl');
            }
        }

        break;
    case 'getindicatorlog':
        require 'form.php';
        $form = new Form($db, $login);
        $indicatorID = (int)$_GET['indicatorID'];
        $series = XSSHelpers::xscrub($_GET['series']);
        $recordID = (int)$_GET['recordID'];

        if (is_numeric($indicatorID))
        {
            $t_form = new Smarty;

            if ($indicatorID > 0 || $series > 0)
            {
                $t_form->assign('log', $form->getIndicatorLog($indicatorID, $series, $recordID));
                $t_form->display('ajaxIndicatorLog.tpl');
            }
        }

        break;
    case 'domodify':
        require 'form.php';
        $form = new Form($db, $login);
        echo $form->doModify((int)$_POST['recordID']);

        break;
    case 'getsubmitcontrol':
        $t_form = new Smarty;
        $recordID = (int)$_GET['recordID'];

        $vars = array('recordID' => $recordID);
        // check if request has a workflow
        $res = $db->prepared_query('SELECT * FROM category_count
                                             LEFT JOIN categories USING (categoryID)
                                             LEFT JOIN workflows USING (workflowID)
                                             WHERE recordID=:recordID
                                               AND count > 0
        									   AND workflowID > 0', $vars);
           // if no workflow, don't give a submit control
           if (count($res) == 0)
           {
               echo '<div style="padding: 8px">Error: This form does not have a workflow associated with it</div>';

               return 0;
           }

        $parallelProcessing = false;
        if(array_key_exists(0,$res) && array_key_exists('type',$res[0]) && ($res[0]['type'] == 'parallel_processing'))
        {
            $parallelProcessing = true;

            // show normal submit control if a parallel request has been sent back
            // a request is assumed to sent back if a matching entry exists in the records_dependencies table
            $vars = array('recordID' => $recordID);
            $res = $db->prepared_query('SELECT * FROM records_dependencies
                                             WHERE recordID=:recordID
                                               AND dependencyID = 5
        									   AND filled = 0', $vars);
            if(isset($res[0])) {
                $parallelProcessing = false;
            }
        }

        $res = $db->prepared_query('SELECT time FROM action_history
                WHERE recordID = :recordID
                LIMIT 1', $vars);

        $lastActionTime = isset($res[0]['time']) ? $res[0]['time'] : 0;

        $requestLabel = $settings['requestLabel'] == '' ? 'Request' : XSSHelpers::sanitizeHTML($settings['requestLabel']);

        $t_form->assign('recordID', $recordID);
        $t_form->assign('lastActionTime', $lastActionTime);
        $t_form->assign('requestLabel', $requestLabel);
        $t_form->assign('orgchartPath', Config::$orgchartPath);
        $t_form->assign('CSRFToken', $_SESSION['CSRFToken']);

        if ($parallelProcessing)
        {
            $t_form->display(customTemplate('submitForm_parallel_processing.tpl'));
        }
        else
        {
            $t_form->display(customTemplate('submitForm.tpl'));
        }

        break;
    case 'dosubmit': // legacy action
        require 'form.php';
        $form = new Form($db, $login);
        $recordID = (int)$_GET['recordID'];
        if (is_numeric($recordID) && $form->getProgress($recordID) >= 100)
        {
            $status = $form->doSubmit($recordID);
            if ($status['status'] == 1)
            {
                echo $recordID . 'submitOK';
            }
            else
            {
                echo $status['errors'];
            }
        }
        else
        {
            echo 'Form is incomplete';
        }

        break;
    case 'cancel':
        require 'form.php';

        if (is_numeric($_POST['cancel']))
        {
            $form = new Form($db, $login);
            echo $form->deleteRecord((int)$_POST['cancel']);
        }

        break;
    case 'restore':
        require 'form.php';

        if (is_numeric($_POST['restore']))
        {
            $form = new Form($db, $login);
            echo $form->restoreRecord((int)$_POST['restore']);
        }

        break;
    case 'doapproval':
        // old
        //require 'Action.php';
        //$approval = new Action($db, $login, $_GET['recordID']);
        //$approval->addApproval($_POST['groupID'], $_POST['status'], $_POST['comment'], $_POST['dependencyID']);
        break;
    case 'doupload': // handle file upload
        require 'form.php';
        $uploadOk = true;
        $uploadedFilename = '';
        foreach ($_FILES as $file)
        {
            if ($file['error'] != UPLOAD_ERR_OK)
            {
                $uploadOk = false;
            }
            $uploadedFilename = $file['name'];
        }

        $body = '';
        $recordID = 0;
        $series = 0;
        $indicatorID = 0;
        $main = new Smarty;
        $t_form = new Smarty;

        if ($uploadOk)
        {
            $form = new Form($db, $login);
            if ($form->doModify($_GET['recordID']))
            {
                $body .= "<b>{$uploadedFilename}</b> has been attached!";
                $recordID = (int)$_GET['recordID'];
                $series = (int)$_POST['series'];
                $indicatorID = (int)$_POST['indicatorID'];

                $t_form->assign('message', $body);
                $t_form->assign('recordID', $recordID);
                $t_form->assign('series', $series);
                $t_form->assign('indicatorID', $indicatorID);
                $main->assign('body', $t_form->fetch('file_form_additional.tpl'));
            }
            else
            {
                $body .= 'File upload error: Please make sure the file you are uploading is either a PDF, Word Document or similar format.';
                $main->assign('body', $body);
            }
        }
        else
        {
            $errorCode = '';
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorCode = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';

                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorCode = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';

                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorCode = 'The uploaded file was only partially uploaded, please try again.';

                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorCode = 'No file was selected to be attached.';

                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorCode = 'Missing a temporary folder';

                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorCode = 'Failed to write file to disk';

                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorCode = 'File upload stopped by extension';

                    break;
                default:
                    $errorCode = 'Unknown upload error';

                    break;
            }

            $body .= 'Error in uploading file: ' . $errorCode;

            $main->assign('body', $body);
        }

        $main->display('main_iframe.tpl');

        break;
    case 'deleteattachment':
        require 'form.php';
        $form = new Form($db, $login);

        echo $form->deleteAttachment((int)$_POST['recordID'], (int)$_POST['indicatorID'], XSSHelpers::xscrub($_POST['series']), XSSHelpers::xscrub($_POST['file']));

        break;
    case 'getstatus':
        require 'form.php';
        require 'View.php';
        $form = new Form($db, $login);
        $view = new View($db, $login);

        $t_form = new Smarty;
        $t_form->left_delimiter = '<!--{';
        $t_form->right_delimiter = '}-->';
        $recordInfo = $form->getRecordInfo((int)$_GET['recordID']);
        $t_form->assign('name', XSSHelpers::sanitizeHTML($recordInfo['name']));
        $t_form->assign('title', XSSHelpers::sanitizeHTML($recordInfo['title']));
        $t_form->assign('priority', (int)$recordInfo['priority']);
        $t_form->assign('submitted', (int)$recordInfo['submitted']);
        $t_form->assign('service', XSSHelpers::sanitizeHTML($recordInfo['service']));
        $t_form->assign('date', $recordInfo['date']);
        $t_form->assign('recordID', (int)$_GET['recordID']);
        $t_form->assign('agenda', $view->buildViewStatus((int)$_GET['recordID']));
        $t_form->assign('dependencies', $form->getDependencyStatus($_GET['recordID']));

        $t_form->display('view_status.tpl');

        break;
    case 'internalview':
    case 'internalonlyview':
    case 'printview':
        if ($login->isLogin())
        {
            require 'form.php';
            $form = new Form($db, $login);
            $recordIDToPrint = (int)$_GET['recordID'];

            $recordInfo = $form->getRecordInfo($recordIDToPrint);

            $categoryText = '';
            if (is_array($recordInfo['categoryNames']))
            {
                foreach ($recordInfo['categoryNames'] as $tName)
                {
                    if ($tName != '')
                    {
                        $categoryText .= $tName . ' | ';
                    }
                }
                $categoryText = trim($categoryText, ' | ');
            }

            $t_form = new Smarty;
            $t_form->left_delimiter = '<!--{';
            $t_form->right_delimiter = '}-->';
            $t_form->assign('recordID', $recordIDToPrint);
            $t_form->assign('name', XSSHelpers::sanitizeHTML($recordInfo['name']));
            $t_form->assign('title', XSSHelpers::sanitizeHTMl($recordInfo['title']));
            $t_form->assign('priority', (int)$recordInfo['priority']);
            $t_form->assign('submitted', (int)$recordInfo['submitted']);
            $t_form->assign('service', XSSHelpers::sanitizeHTMl($recordInfo['service']));
            $t_form->assign('date', $recordInfo['submitted']);
            $t_form->assign('categoryText', XSSHelpers::sanitizeHTML($categoryText));
            $t_form->assign('deleted', (int)$recordInfo['deleted']);
            $t_form->assign('orgchartPath', Config::$orgchartPath);
            $t_form->assign('is_admin', $login->checkGroup(1));

            switch ($action) {
                case 'internalonlyview':
                    $t_form->assign('form', $form->getFullForm($recordIDToPrint, XSSHelpers::xssafe($_GET['childCategoryID'])));

                    break;
                default:
                    $t_form->assign('form', $form->getFullForm($recordIDToPrint));

                    break;
            }

            // get tags
            $t_form->assign('tags', $form->getTags($recordIDToPrint));

            if (!isset($_GET['enclosed']))
            {
                $childForms = $form->getChildForms($recordIDToPrint);
                $tChildForms = array();
                foreach ($childForms as $childForm)
                {
                    $tChildForms[$childForm['childCategoryID']] = $childForm['childCategoryName'];
                }

                $t_form->assign('subtype', isset($_GET['childCategoryID']) ? '(' . strip_tags($tChildForms[XSSHelpers::xssafe($_GET['childCategoryID'])]) . ')' : '');
                $t_form->display(customTemplate('print_form_ajax.tpl'));
            }
            else
            {
                $t_login = new Smarty;
                $t_login->assign('name', $login->getName());

                $main = new Smarty;
                if ($recordInfo['priority'] == -10)
                {
                    $main->assign('emergency', '<span style="position: absolute; right: 0px; top: -28px; padding: 2px; border: 1px solid black; background-color: white; color: red; font-weight: bold; font-size: 20px">EMERGENCY</span> ');
                }
                $main->assign('body', $t_form->fetch(customTemplate('print_form_ajax.tpl')));
                $tabText = 'Request #' . (int)$_GET['recordID'];
                $main->assign('tabText', $tabText);

                $main->assign('logo', '<img src="images/VA_icon_small.png" style="width: 80px" alt="VA logo" />');

                $main->assign('login', $t_login->fetch('login.tpl'));
                $main->display('main.tpl');
            }
        }

        break;
    case 'gettags':
        require 'form.php';
        $form = new Form($db, $login);
        if (is_numeric($_GET['recordID']))
        {
            $t_form = new Smarty;

            if ($_GET['recordID'] > 0)
            {
                $t_form->assign('tags', $form->getTags((int)$_GET['recordID']));
                $t_form->display('print_form_ajax_tags.tpl');
            }
        }

        break;
    case 'getformtags':
        require 'form.php';
        $form = new Form($db, $login);
        if (is_numeric($_GET['recordID']))
        {
            $t_form = new Smarty;

            if ($_GET['recordID'] > 0)
            {
                $t_form->assign('tags', $form->getTags((int)$_GET['recordID']));
                $t_form->display('form_tags.tpl');
            }
        }

        break;
    case 'gettagmembers':
        require 'form.php';
        $form = new Form($db, $login);
        $t_form = new Smarty;
        $t_form->left_delimiter = '<!--{';
        $t_form->right_delimiter = '}-->';

        $tagMembers = $form->getTagMembers(XSSHelpers::xscrub($_GET['tag']));

        $t_form->assign('tag', XSSHelpers::xscrub($_GET['tag']));
        $t_form->assign('totalNum', count($tagMembers));
        $t_form->assign('requests', $tagMembers);
        $t_form->display('tag_show_members.tpl');

        break;
    case 'updatetags':
        require 'form.php';
        $form = new Form($db, $login);
        $form->parseTags((int)$_POST['recordID'], XSSHelpers::xscrub($_POST['taginput']));

        break;
    case 'addbookmark':
        if ($_POST['CSRFToken'] != $_SESSION['CSRFToken'])
        {
            exit();
        }
        require 'form.php';
        $form = new Form($db, $login);
        $form->addTag((int)$_GET['recordID'], 'bookmark_' . XSSHelpers::xscrub($login->getUserID()));

        break;
    case 'removebookmark':
        if ($_POST['CSRFToken'] != $_SESSION['CSRFToken'])
        {
            exit();
        }
        require 'form.php';
        $form = new Form($db, $login);
        $form->deleteTag((int)$_GET['recordID'], 'bookmark_' . XSSHelpers::xscrub($login->getUserID()));

        break;
    default:
        break;
}
