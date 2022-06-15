<p style=" font-size: 16px; color: #626262; margin: -42px 0 0px 0; border-bottom: 1px solid #e9eaeb; padding-bottom: 20px;" >نیازمندی‌های سرور میزبانی</p>

<p class="red">
    سرور میزبانی شما تمامی نیازمندی‌های مورد نیاز را ندارد.
</p>

<ul class="ow_regular">
<!-- PHP version -->
<?php if ( !empty($_assign_vars['fails']['php']['version']) ) { $requiredVersion = $_assign_vars['fails']['php']['version']; ?>
    
        <li>
            نیازمند نسخه PHP: <b class="high"><?php echo $requiredVersion ?></b> یا بالاتر <span class="small">(در حال حاضر <b><?php echo $_assign_vars['current']['php']['version']; ?></b>)</span>
        </li>
    
<?php } ?>

<!-- PHP extensions -->
<?php if ( !empty($_assign_vars['fails']['php']['extensions']) ) { ?>
    <?php foreach ($_assign_vars['fails']['php']['extensions'] as $requiredExt) { ?>
        
        <li>
               <b class="high"><?php echo $requiredExt; ?></b> افزونه‌های PHP نصب نشده است.
        </li>    
            
    <?php } ?>
<?php } ?>

<!-- INI Configs -->
<?php if ( !empty($_assign_vars['fails']['ini']) ) { ?>
    
        <?php foreach ($_assign_vars['fails']['ini'] as $iniName => $iniValue) { ?>
        
            <li>
                   <span class="high"><?php echo $iniName; ?></span> باید <b class="high"><?php echo $iniValue ? 'on' : 'off'; ?></b> باشد
                   <span class="small">(در حال حاضر <b><?php echo $_assign_vars['current']['ini'][$iniName] ? 'on' : 'off'; ?></b>)</span>
            </li>    
                
        <?php } ?>
    
<?php } ?>

<!-- GD version -->
<?php if ( !empty($_assign_vars['fails']['gd']['version']) ) { $requiredVersion = $_assign_vars['fails']['gd']['version']; ?>
    
        <li>
               Required <span class="high">GD کتابخانه</span> نسخه: <b class="high"><?php echo $requiredVersion ?></b> یا بالاتر
               <span class="small">(در حال حاضر <b><?php echo $_assign_vars['current']['gd']['version']; ?></b>)</span>
        </li>
    
<?php } ?>

<!-- GD support -->
<?php if ( !empty($_assign_vars['fails']['gd']['support']) ) { $requiredSupportType = $_assign_vars['fails']['gd']['support']; ?>
    
        <li>
               <b class="high"><?php echo $requiredSupportType ?></b> ضروری است برای <span class="high">GD کتابخانه</span>
        </li>
    
<?php } ?>

</ul>

<p>
    لطفا تمامی نیازمندی‌های سرور میزبانی را برآورده کرده و فرایند نصب را ادامه دهید.
</p>