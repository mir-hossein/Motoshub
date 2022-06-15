<p style=" font-size: 16px; font-family:Arial; color: #626262; margin: -42px 0 0px 0; border-bottom: 1px solid #e9eaeb; padding-bottom: 20px;" >نهایی کردن فرایند نصب</p>
<?php echo install_tpl_feedback(); ?>

<?php
if ( $_assign_vars['dirs'] )
{
?>
<div class="feedback_msg error">
	شما باید امنیت نوشتن را بصورت بازگشتی در این پوشه‌ها اعمال کنید: (<a target="_blank" href="http://docs.shub.ir/doku.php?id=%D9%86%D8%B5%D8%A8_%D9%85%D9%88%D8%AA%D9%88%D8%B4%D8%A7%D8%A8"> <b>؟</b></a>)
</div>

<ul class="directories">
    <?php foreach ($_assign_vars['dirs'] as $dir) { ?>
	    <li><?php echo $dir; ?></li>
	<?php } ?>
</ul>

<hr />
<?php
}
?>
<form method="post">
    <div style="<?= $_assign_vars['isConfigWritable'] ? 'display: none;' : ''; ?>" >
        <p>
            لطفا کد پایین را کپی کرده و به جای کدهای قبلی وارد نمایید. این کار را باید در فایل <b>ow_includes/config.php</b> انجام دهید. مطمئن باشید که هیچ فاصله اضافی بین حروف و کلمات قرار نگیرد.
        </p>
        <textarea rows="5" name="configContent" class="config" style="height: 400px;" onclick="this.select();"><?php echo $_assign_vars['configContent']; ?></textarea>
        <input type="hidden" name="isConfigWritable" value="<?= $_assign_vars['isConfigWritable'] ? '1' : '0'; ?>" />
    </div>
    <p style="text-align: center; color: #626262; padding-top: 19px; ">
        یک کرون جاب ایجاد کنید تا فایل  <b>ow_cron/run.php</b> را در هر دقیقه اجرا کند.
        (
        <a style="color:#2626ef;" target="_blank" href="http://docs.shub.ir/doku.php?id=%D9%86%D8%B5%D8%A8_%D9%85%D9%88%D8%AA%D9%88%D8%B4%D8%A7%D8%A8"><b>؟</b>
        </a>
        )
    </p>
    <p align="center"><input type="submit" value="ادامه" name="continue" style=" margin-bottom: 19px; text-transform: uppercase; font-size: 13px; font-family: 'Arial'; font-weight: bold; color: white; /></p>
</form>