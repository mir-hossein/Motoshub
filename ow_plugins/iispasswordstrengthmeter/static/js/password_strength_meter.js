function passwordStrengthMeter(minimumCharacter, minimumRequirementPasswordStrength){

    var forms = ['joinForm','change-user-password','reset-password'];
    for(var i=0; i<forms.length;i++){
        if($("form[name='"+forms[i]+"']").length > 0 ) {
            var password = $("form[name='"+ forms[i] +"']  input[name='password']");
            var repeatPassword = $("form[name='"+ forms[i] +"']  input[name='repeatPassword']");
            $("#" + password[0].id).keyup(function () {
                validatePasswordStrength(password,minimumCharacter,minimumRequirementPasswordStrength);
                comparePasswordAndrepeatPassword(password,repeatPassword);
            });
            $("#" + repeatPassword[0].id).keyup(function () {
                comparePasswordAndrepeatPassword(password,repeatPassword);
            });

            $("#" + password[0].id).change(function () {
                validatePasswordStrength(password,minimumCharacter,minimumRequirementPasswordStrength);
            });
            validatePasswordStrength(password,minimumCharacter,minimumRequirementPasswordStrength);
        }
    }
}

function comparePasswordAndrepeatPassword(password,repeatPassword)
{
    var strengthDivPlace = document.getElementById(repeatPassword[0].id + '_error');
    if (password.val() == repeatPassword.val() || password.val()=="" || repeatPassword.val()=="") {
        $("#" + repeatPassword[0].id + '_error').css('display', 'none');
    } else
    {
        strengthDivPlace.innerHTML = OW.getLanguageText('iispasswordstrengthmeter', 'password_repeatpassword_compare_error');
        $("#" + repeatPassword[0].id + '_error').css('display', 'block');
    }
}
function validatePasswordStrength(password, minimumCharacter,minimumRequirementPasswordStrength){

    $password_strength_meter_id = "password_strength_meter_"+password[0].id;
    $password_strength_meter_id_table = $password_strength_meter_id+"_table";
    if(password.val().length == 0){
        $("#" + $password_strength_meter_id).css('display', 'none');
    }

    var hasNumber = 0;
    var hasUpperCase = 0;
    var hasLowerCase = 0;
    var hasSpecialCharacter = 0;

    var replaceList = [" ","!","\"","#","$","%","&","'","(",")","*","+","\,","-",".","/",":",";","<","=",">","?","@","[","\\","]","^","_","`","{","|","}","~"];

    var i = 0;
    while (i < password.val().length) {
        character = password.val().charAt(i);
        if (replaceList.indexOf(character)>-1) {//count of special character
            hasSpecialCharacter = 1;
        }else if (character != " " && !isNaN(character * 1)) {//count of number
            hasNumber = 1;
        }else if (character == character.toUpperCase()) {//count of uppercase
            hasUpperCase = 1;
        }else if (character == character.toLowerCase()) {//count of lowercase
            hasLowerCase = 1;
        }
        i++;
    }

    var state = [];

    state['poor_label'] = OW.getLanguageText('iispasswordstrengthmeter', 'strength_poor_label');
    state['poor_color1'] = 'rgb(200,80,80)';
    state['poor_color2'] = 'transparent';
    state['poor_color3'] = 'transparent';
    state['poor_color4'] = 'transparent';


    state['weak_label'] = OW.getLanguageText('iispasswordstrengthmeter', 'strength_weak_label');
    state['weak_color1'] = 'rgb(255,220,150)';
    state['weak_color2'] = 'rgb(255,166,12)';
    state['weak_color3'] = 'transparent';
    state['weak_color4'] = 'transparent';

    state['good_label'] = OW.getLanguageText('iispasswordstrengthmeter', 'strength_good_label');
    state['good_color1'] = 'rgb(255,255,200)';
    state['good_color2'] = 'rgb(255,255,100)';
    state['good_color3'] = 'rgb(255,255,0)';
    state['good_color4'] = 'transparent';

    state['excellent_label'] = OW.getLanguageText('iispasswordstrengthmeter', 'strength_excellent_label');
    state['excellent_color1'] = 'rgb(230,255,230)';
    state['excellent_color2'] = 'rgb(200,255,200)';
    state['excellent_color3'] = 'rgb(150,255,150)';
    state['excellent_color4'] = 'rgb(0,255,0)';

    currentState = 'poor';
    if(password.val().length< minimumCharacter || (password.val().length < 2*minimumCharacter && hasNumber+hasUpperCase+hasSpecialCharacter == 0 )){
        currentState = 'poor';
    }else if(hasNumber+hasUpperCase+hasSpecialCharacter+hasLowerCase==1){
        currentState = 'weak';
    }else if(hasNumber+hasUpperCase+hasSpecialCharacter+hasLowerCase==2){
        currentState = 'good';
    }else if(hasNumber+hasUpperCase+hasSpecialCharacter+hasLowerCase >= 3){
        currentState = 'excellent';
    }

    var label = state[currentState+'_label'];
    var color1 = state[currentState+'_color1'];
    var color2 = state[currentState+'_color2'];
    var color3 = state[currentState+'_color3'];
    var color4 = state[currentState+'_color4'];

    var htmlCode = '<div class="password_strength_meter_parent" id="'+$password_strength_meter_id+'">' +
        '<table class="password_strength_meter" id="'+$password_strength_meter_id_table+'">' +
        '<tr>' +
        '<td style="background-color: '+color1+'"></td>' +
        '<td style="background-color: '+color2+'"></td>' +
        '<td style="background-color: '+color3+'"></td>' +
        '<td style="background-color: '+color4+'"></td>' +
        '<td>'+label+'</td>' +
        '</tr>' +
        '</table>' +
        '<table class="password_strength_meter_information">' +
        '<tr>' +
        '<td colspan="5"><span id="secure_password_minimum_strength_type">'+OW.getLanguageText("iispasswordstrengthmeter", "secure_password_information_minimum_strength_type",{'value':minimumRequirementPasswordStrength})+'</span></td>' +
        '</tr>' +
        '<tr>' +
        '<td colspan="5"><a href="javascript:secure_password_information()" id="secure_password_information">'+OW.getLanguageText("iispasswordstrengthmeter", "secure_password_information_title")+'</a></td>' +
        '</tr>' +
        '</table>' +
        '</div>';

    var innerHtmlCode = '<tr>' +
        '<td style="background-color: '+color1+'"></td>' +
        '<td style="background-color: '+color2+'"></td>' +
        '<td style="background-color: '+color3+'"></td>' +
        '<td style="background-color: '+color4+'"></td>' +
        '<td>'+label+'</td>' +
        '</tr>';


    var strengthDivPlace = document.getElementById(password[0].id + '_error');
    if(password.val().length > 0 ) {
        if (document.getElementById($password_strength_meter_id_table) == null) {
            if (document.getElementById(password[0].id + '_error')) {
                strengthDivPlace.innerHTML = strengthDivPlace.innerHTML + htmlCode;
            } else {
                $(htmlCode).insertAfter(password);
            }
        } else {
            document.getElementById($password_strength_meter_id_table).innerHTML = innerHtmlCode;
        }

        if(strengthDivPlace){
            $("#" + password[0].id + '_error').css('display', 'block');
            $("#" + $password_strength_meter_id).css('display', 'table');
        }else{
            $("#" + $password_strength_meter_id).css('display', 'table');
        }
    }

}

function secure_password_information(){
    OW.ajaxFloatBox('IISPASSWORDSTRENGTHMETER_CMP_Securepassword', {} , {width:480, iconClass: 'ow_ic_add', title: OW.getLanguageText('iispasswordstrengthmeter', 'secure_password_information_title')});
}