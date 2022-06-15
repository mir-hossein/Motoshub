function gregorian_to_jalali($g_y, $g_m, $g_d) {
    $d_4 = $g_y % 4;
    $g_a = [0, 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $doy_g = $g_a[parseInt($g_m)] + $g_d;
    if ($d_4 == 0 && $g_m > 2) {
        $doy_g++;
    }
    $d_33 = parseInt((($g_y - 16) % 132) * .0305);

    if ($d_33 == 3 || $d_33 < ($d_4 - 1) || $d_4 == 0) {
        $a = 286;
    }
    else {
        $a = 287;
    }
    if (($d_33 == 1 || $d_33 == 2) && ($d_33 == $d_4 || $d_4 == 1)) {
        $b = 78;
    }
    else {
        if ($d_33 == 3 && $d_4 == 0) {
            $b = 80;
        }
        else {
            $b = 79;
        }
    }
    if (parseInt(($g_y - 10) / 63) == 30) {
        $a--;
        $b++;
    }
    if ($doy_g > $b) {
        $jy = $g_y - 621;
        $doy_j = $doy_g - $b;
    } else {
        $jy = $g_y - 622;
        $doy_j = $doy_g + $a;
    }
    if ($doy_j < 187) {
        $jm = parseInt(($doy_j - 1) / 31);
        $jd = $doy_j - (31 * $jm++);
    } else {
        $jm = parseInt(($doy_j - 187) / 30);
        $jd = $doy_j - 186 - ($jm * 30);
        $jm += 7;
    }
    return [$jy, $jm, $jd];
}


function jalali_to_gregorian($j_y, $j_m, $j_d) {
    $d_4 = ($j_y + 1) % 4;
    if ($j_m < 7) {
        $doy_j = (($j_m - 1) * 31) + $j_d;
    }
    else {
        $doy_j = (($j_m - 7) * 30) + $j_d + 186;
    }
    $d_33 = parseInt((($j_y - 55) % 132) * .0305);
    if ($d_33 != 3 && $d_4 <= $d_33) {
        $a = 287;
    }
    else {
        $a = 286;
    }
    if (($d_33 == 1 || $d_33 == 2) && ($d_33 == $d_4 || $d_4 == 1)) {
        $b = 78;
    }
    else {
        if ($d_33 == 3 && $d_4 == 0) {
            $b = 80;
        }
        else {
            $b = 79;
        }
    }
    if (parseInt(($j_y - 19) / 63) == 20) {
        $a--;
        $b++;
    }
    if ($doy_j <= $a) {
        $gy = $j_y + 621;
        $gd = $doy_j + $b;
    } else {
        $gy = $j_y + 622;
        $gd = $doy_j - $a;
    }
    var kk;
    if ($gy % 4 == 0) {
        kk = 29;
    }
    else {
        kk = 28;
    }
    var ss = [0, 31, kk, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var $gm;
    var $v;
    for ($gm = 0; $gm < ss.length; $gm++) {
        if ($gd <= ss[$gm])
            break;
        $gd -= ss[$gm];
    }
    return [$gy, $gm, $gd];
}

function changeTopersianNum(num) {
    var converted = '';
    for (var i=0; i<num.toString().length;i++){
        converted += changeTopersianCharacter(num.toString()[i]);
    }
    return converted;
}

function changeTopersianCharacter(char) {
    persianNum = {0: '۰', 1: '۱', 2: '۲', 3: '۳', 4: '۴', 5: '۵', 6: '۶', 7: '۷', 8: '۸', 9: '۹', '.': ','};
    if (persianNum[char] == 'undefined') {
        return char;
    }
    return persianNum[char];
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return "";
}

function isLeapYear(year)
{
    year = parseInt(year);
    var a = 0.025;
    var b = 266;
    var leapDays0;
    var leapDays1;
    if (year > 0)
    {
        leapDays0 = (((year + 38) % 2820)*0.24219) + a;
        leapDays1 = (((year + 39) % 2820)*0.24219) + a;
    }
    else if (year < 0)
    {
        leapDays0 = (((year + 39) % 2820)*0.24219) + a;
        leapDays1 = (((year + 40) % 2820)*0.24219) + a;
    }

    var frac0 = parseInt((leapDays0 - parseInt(leapDays0))*1000);
    var frac1 = parseInt((leapDays1 - parseInt(leapDays1))*1000);
    return (frac0 <= b && frac1 > b )
}