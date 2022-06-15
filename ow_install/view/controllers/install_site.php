<p style=" font-size: 16px; color: #626262; margin: -42px 0 18px 0; border-bottom: 1px solid #e9eaeb; padding-bottom: 20px;" >تنظیمات سایت</p>

<?php echo install_tpl_feedback(); ?>

<form method="post">
	<table class="form">
	    <tr style="color: #626262; font-size: 15px;"><th colspan="3">اطلاعات سایت</th></tr>
	    <tr>
	        <td class="label">عنوان سایت</td>
	        <td class="value <?php echo install_tpl_feedback_flag('site_title'); ?>">
	           <input type="text" name="site_title" value="<?php echo @$_assign_vars['data']['site_title']; ?>" />
	        </td>
	        <td class="description"></td>
	    </tr>
	    <tr>
	        <td class="label">شعار سایت</td>
	        <td class="value <?php echo install_tpl_feedback_flag('site_tagline'); ?>">
	           <input type="text" name="site_tagline" value="<?php echo @$_assign_vars['data']['site_tagline']; ?>" />
	        </td>
	        <td class="description">یک رشته جاذب سایت <br> توضیحات (اختیاری)</td>
	    </tr>
	    <tr>
	        <td class="label">نشانی اینترنتی</td>
	        <td class="dirLeft value <?php echo install_tpl_feedback_flag('site_url'); ?>">
	           <input type="text" name="site_url" value="<?php echo @$_assign_vars['data']['site_url']; ?>" />
	        </td>
	        <td class="description"></td>
	    </tr>
	    <tr>
	        <td class="label">دایرکتوری ریشه</td>
	        <td class="dirLeft value <?php echo install_tpl_feedback_flag('site_path'); ?>">
	           <input type="text" name="site_path" value="<?php echo @$_assign_vars['data']['site_path']; ?>" />
            </td>
	        <td class="description"></td>
	    </tr>
	    <tr style="color: #626262; font-size: 15px; padding-bottom: 24px;"><th colspan="3">مدیر سایت</th></tr>
	    <tr>
	        <td class="label">رایانامه</td>
	        <td class="dirLeft value <?php echo install_tpl_feedback_flag('admin_email'); ?>">
	           <input type="text" name="admin_email" value="<?php echo @$_assign_vars['data']['admin_email']; ?>" />
	        </td>
	        <td class="description"></td>
	    </tr>
	    <tr>
	        <td class="label">نام کاربری</td>
	        <td class="dirLeft value <?php echo install_tpl_feedback_flag('admin_username'); ?>">
               <input type="text" name="admin_username" value="<?php echo @$_assign_vars['data']['admin_username']; ?>" />
            </td>
	        <td class="description">تنها شامل حروف و اعداد</td>
	    </tr>
	    <tr>
	        <td class="label">گذرواژه</td>
	        <td class="dirLeft value <?php echo install_tpl_feedback_flag('admin_password'); ?>">
	           <input type="text" name="admin_password" value="<?php echo @$_assign_vars['data']['admin_password']; ?>" />
	        </td>
	        <td class="description">تعداد کاراکتر از 2 تا 12</td>
	    </tr>
	</table>

	<p align="center"><input type="submit" value="ادامه" style=" text-transform: uppercase; font-size: 13px; font-weight: bold; color: white; /></p>

</form>