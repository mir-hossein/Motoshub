<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
define('_OW_', true);

define('DS', DIRECTORY_SEPARATOR);

define('OW_DIR_ROOT', dirname(__FILE__) . DS);

require_once(OW_DIR_ROOT . 'ow_includes' . DS . 'init.php');
$session = OW_Session::getInstance();
$session->start();
$errorDetails = '';

if ( $session->isKeySet('errorData') )
{
    $errorData = unserialize($session->get('errorData'), ['allowed_classes' => false]);
    $trace = '';

    if ( !empty($errorData['trace']) )
    {
        $trace = '<tr>
                        <td class="lbl">Trace:</td>
                        <td class="cnt">' . $errorData['trace'] . '</td>
                </tr>';
    }

    $errorDetails = '<div style="margin-top: 30px;">
            <b>Error details</b>:
            <table style="font-size: 13px;">
                <tbody>
                <tr>
                        <td class="lbl">Type:</td>
                        <td class="cnt">' . $errorData['type'] . '</td>
                </tr>
                <tr>
                        <td class="lbl">Message:</td>
                        <td class="cnt">' . $errorData['message'] . '</td>
                </tr>
                <tr>
                        <td class="lbl">File:</td>
                        <td class="cnt">' . $errorData['file'] . '</td>
                </tr>
                <tr>
                        <td class="lbl">Line:</td>
                        <td class="cnt">' . $errorData['line'] . '</td>
                </tr>
                ' . $trace . '
        </tbody></table>
        </div>';
}

$output = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body style="padding-top:10px; padding-right: 10px; font:12px Tahoma; direction: rtl; text-align: right;">
  <div style="border-radius: 10px; -moz-border-radius: 10px; -webkit-border-radius: 10px; text-align: center; padding: 15px; display: block; background-color: lightblue">
  <a style="background-color: #0042b5; color: white; text-decoration: none; padding: 10px; border-radius: 10px; -moz-border-radius: 10px; -webkit-border-radius: 10px;" href="'.OW_URL_HOME.'">
    بازگشت به صفحه اصلی
    </a>
    <br/>
    <div style="text-align: right; margin-top: 15px; display: block; border-bottom: 1px solid #666; padding-bottom: 6px; margin-bottom: 8px;">
    خطایی در سامانه رخ داده است. (خطای 500)
    </div><br/>
    <div style="text-align: right; font-size: 13px; margin-bottom: 4px;">
    اگر کاربر مدیر هستید،
    <a href="javascript://" onclick="getElementById(\'hiddenNode\').style.display=\'block\'">
        برای مشاهده خطا اینجا را کلیک کنید.
    </a></div>
    <div style="text-align: right; font-size: 13px; display: none;" id="hiddenNode">
        <div style="margin-top: 30px;">
    	<b style="line-height: 24px;">
    	    خطایی در سامانه وجود دارد
        </b>!<br/>
    	به منظور شناسایی خطا، مراحل زیر را طی کنید:
    	<br/>
        - فایل <i>ow_includes/config.php</i> را باز کرده و مقدار متغیر  <b>DEBUG_MODE</b> را به  <b>true</b> تغییر دهید.<br/>
 		- سناریو خطا را مجددا بررسی نمایید.
       </div>
        ' . $errorDetails . '
    </div>
    </div>
  </body>
</html>
';

echo $output;

