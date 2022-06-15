<p style="font-size: 16px; margin: -42px 0px 0px; color: rgb(98, 98, 98);border-bottom: 1px solid #e9eaeb; padding-bottom: 20px;">پایگاه داده </p>

<?php echo install_tpl_feedback(); ?>
<form method="post">
    <table style=" font-size: 15px;" class="dirLeftTd form"> <p style=" color: #626262; text-align: center;">لطفا پایگاه داده را ایجاد کرده و اطلاعات آن را وارد نمایید.</p>

        <tr> 
            <td class="label">میزبان</td>
            <td class="value <?php echo install_tpl_feedback_flag('db_host'); ?>">
               <input type="text" name="db_host" value="<?php echo @$_assign_vars['data']['db_host']; ?>" />
            </td>
            <td class="description">
                میزبانی و پورت (اختیاری) MySQL.
                <br/>
                برای مثال:  <i>localhost</i> یا <i>localhost:3306</i>
            </td>
        </tr>
        <tr>
            <td class="label">نام کاربری</td>
            <td class="value <?php echo install_tpl_feedback_flag('db_user'); ?>">
               <input type="text" name="db_user" value="<?php echo @$_assign_vars['data']['db_user']; ?>" />
            </td>
            <td class="description"> </td>
        </tr>
        <tr>
            <td class="label">گذرواژه</td>
            <td class="value <?php echo install_tpl_feedback_flag('db_password'); ?>">
               <input type="text" name="db_password" value="<?php echo @$_assign_vars['data']['db_password']; ?>" />
            </td>
            <td class="description"> </td>
        </tr>
        
        <tr>
            <td class="label">نام پایگاه داده</td>
            <td class="value <?php echo install_tpl_feedback_flag('db_name'); ?>">
               <input type="text" name="db_name" value="<?php echo @$_assign_vars['data']['db_name']; ?>" />
            </td>
            <td class="description"> </td>
        </tr>
        
        <tr>
            <td class="label">پیشوند جداول</td>
            <td class="value <?php echo install_tpl_feedback_flag('db_prefix'); ?>">
               <input type="text" name="db_prefix" value="<?php echo @$_assign_vars['data']['db_prefix']; ?>" />
            </td>
            <td class="description"> </td>
        </tr>
    </table>

    <p align="center" style="margin: 10px 0 20px 0;" ><input type="submit" value="ادامه" style=" text-transform: uppercase; font-size: 13px; font-weight: bold; color: white;/></p>

</form>