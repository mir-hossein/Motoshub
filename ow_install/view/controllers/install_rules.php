<p style=" font-size: 16px; color: #626262; margin: -42px 0 0px 0; border-bottom: 1px solid #e9eaeb; padding-bottom: 20px;">
    قوانین سایت
</p>

<?php echo install_tpl_feedback(); ?>

<div class="rules_items">
    <table class="form">
        <tr style="color: #626262; font-size: 15px;">
            <th colspan="3">
                تذکر مهم
            </th>
        </tr>
        <tr>
            <td>
                با پایان نصب و راه‌اندازی شبکه اجتماعی، شما به عنوان مدیر شبکه ملزم به رعایت قوانین جمهوری اسلامی ایران و الزاماتی هستید که توسط مراجع ذی‌صلاح، برای فعالیت شبکه‌های اجتماعی تدوین شده است. بنابراین لازم است تا پیش از ارائه خدمات خود برای عموم کاربران، از قوانین و الزامات ناظر بر فعالیت پایگاه‌های اینترنتی و شبکه‌های اجتماعی آگاه شوید.
            </td>
        </tr>
        <tr>
            <td>
                در زیر نمونه‌هایی از موارد مذکور قرار گرفته‌اند. (دقت داشته باشید که این پیوندها تنها به عنوان نمونه و برای آشنایی شما معرفی شده‌اند. برای اطمینان از آشنایی با کلیه قوانین و الزامات مرتبط با فعالیت خود، لازم است از مراجع ذی‌صلاح استعلام نمایید.)
            </td>
        </tr>
        <tr>
            <td>
                <ul>
                    <li>
                        <a href="http://rc.majlis.ir/fa/law/show/135717" target="_blank">
                            قانون جرائم رایانه‌ای
                        </a>
                    </li>
                    <li>
                        <a href="http://rc.majlis.ir/fa/law/show/93997" target="_blank">
                            قانون تجارت الکترونیکی
                        </a>
                    </li>
                    <li>
                        <a href="http://rc.majlis.ir/fa/law/show/93463" target="_blank">
                            قانون حمایت از حقوق پدیدآورندگان نرم‌افزارهای رایانه‌ای
                        </a>
                    </li>
                    <li>
                        <a href="http://rc.majlis.ir/fa/law/show/897504" target="_blank">
                            آیین‌نامه جمع‌آوری و استنادپذیری ادله الکترونیکی
                        </a>
                    </li>
                    <li>
                        <a href="http://internet.ir/crime_index.html" target="_blank">
                            فهرست مصادیق محتوای مجرمانه
                        </a>
                    </li>
                    <li>
                        <a href="https://shub.ir/rules" target="_blank">
                            توصیه‌های امنیت و حریم خصوصی در شبکه‌های اجتماعی
                        </a>
                    </li>
                </ul>
            </td>
        </tr>
    </table>
</div>

<form method="post">
    <table class="form">
        <tr>
            <td class="value <?php echo install_tpl_feedback_flag('site_title'); ?>">
                <span class="rule_accept_description">
                    قوانین مرتبط را مطالعه کردم و به آن‌ها پای‌بندی دارم.
                </span>
                <input type="checkbox" name="rules_accepted"
                       value="<?php echo @$_assign_vars['data']['rules_accepted']; ?>"/>
            </td>
        </tr>
        <?php
        if (install_tpl_feedback_flag('rules_accepted') == 'error') {
            echo '<tr><td style="text-align: center;color: red;">لطفا تیک تایید خواندن قوانین را بزنید.</td></tr>';
        }
        ?>
    </table>

    <p align="center"><input type="submit" value="ادامه" style=" text-transform: uppercase; font-size: 13px; font-weight: bold; color: white; /></p>

</form>