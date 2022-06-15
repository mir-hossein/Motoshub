/**
 * @version 1.0.0
 * @author   Yaser Alimardany
 */

function showdate() {
    a = new Date(new Date());
    d = a.getDay();
    day = a.getDate();
    horuphcmsmonth = a.getMonth() + 1;
    year = a.getYear();
    year = (year == 0) ? 2000 : year;
    (year < 1000) ? (year += 2000) : true;
    year -= ((horuphcmsmonth < 3) || ((horuphcmsmonth == 3) && (day < 21))) ? 622 : 621;
    switch (horuphcmsmonth) {
        case 1:
            (day < 21) ? (horuphcmsmonth = 10, day += 10) : (horuphcmsmonth = 11, day -= 20);
            break;
        case 2:
            (day < 20) ? (horuphcmsmonth = 11, day += 11) : (horuphcmsmonth = 12, day -= 19);
            break;
        case 3:
            (day < 21) ? (horuphcmsmonth = 12, day += 9) : (horuphcmsmonth = 1, day -= 20);
            break;
        case 4:
            (day < 21) ? (horuphcmsmonth = 1, day += 11) : (horuphcmsmonth = 2, day -= 20);
            break;
        case 5:
        case 6:
            (day < 22) ? (horuphcmsmonth -= 3, day += 10) : (horuphcmsmonth -= 2, day -= 21);
            break;
        case 7:
        case 8:
        case 9:
            (day < 23) ? (horuphcmsmonth -= 3, day += 9) : (horuphcmsmonth -= 2, day -= 22);
            break;
        case 10:
            (day < 23) ? (horuphcmsmonth = 7, day += 8) : (horuphcmsmonth = 8, day -= 22);
            break;
        case 11:
        case 12:
            (day < 22) ? (horuphcmsmonth -= 3, day += 9) : (horuphcmsmonth -= 2, day -= 21);
            break;
        default:
            break;
    }
    document.getElementById("azanday").value = day;
    document.getElementById("azanhoruphcmsmonth").value = horuphcmsmonth;
}
function coord(longitude, latitude) {
    document.getElementById("longitude").value = longitude;
    document.getElementById("latitude").value = latitude;
}
var cityChanged = true;
function main_oghat(cityName, $imgOghat1, $imgOghat2, $timeUrl) {
    showdate();
    if (cityChanged){
        document.getElementById("cities").innerHTML = cityName;
        cityChanged = false;
    }
    document.getElementById('displ').style.display = 'none';
    var m = document.getElementById("azanhoruphcmsmonth").value;
    var d = eval(document.getElementById("azanday").value);
    var lg = eval(document.getElementById("longitude").value);
    var lat = eval(document.getElementById("latitude").value);
    var ep = sun(m, d, 4, lg);
    var zr = ep[0];
    delta = ep[1];
    ha = loc2hor(108.0, delta, lat);
    var t1 = Round(zr - ha, 24);
    ep = sun(m, d, t1, lg);
    zr = ep[0];
    delta = ep[1];
    ha = loc2hor(108.0, delta, lat);
    var t1 = Round(zr - ha + 0.025, 24);

    document.getElementById("azan_t1").innerHTML = hms(t1);
    document.getElementById("azan_ht1").value = hhh(t1);
    document.getElementById("azan_mt1").value = mmm(t1);
    ep = sun(m, d, 6, lg);
    zr = ep[0];
    delta = ep[1];
    ha = loc2hor(90.833, delta, lat);
    var t2 = Round(zr - ha, 24);
    ep = sun(m, d, t2, lg);
    zr = ep[0];
    delta = ep[1];
    ha = loc2hor(90.833, delta, lat);
    t2 = Round(zr - ha + 0.008, 24);
    document.getElementById("azan_t2").innerHTML = hms(t2);
    document.getElementById("azan_ht2").value = hhh(t2);
    document.getElementById("azan_mt2").value = mmm(t2);
    ep = sun(m, d, 12, lg);
    ep = sun(m, d, ep[0], lg);
    zr = ep[0] + 0.01;
    document.getElementById("azan_t3").innerHTML = hms(zr);
    document.getElementById("azan_ht3").value = hhh(zr);
    document.getElementById("azan_mt3").value = mmm(zr);
    ep = sun(m, d, 18, lg);
    zr = ep[0];
    delta = ep[1];
    ha = loc2hor(90.833, delta, lat);
    var t3 = Round(zr + ha, 24);
    ep = sun(m, d, t3, lg);
    zr = ep[0];
    delta = ep[1];
    ha = loc2hor(90.833, delta, lat);
    t3 = Round(zr + ha - 0.014, 24);
    document.getElementById("azan_t4").innerHTML = hms(t3);
    document.getElementById("azan_ht4").value = hhh(t3);
    document.getElementById("azan_mt4").value = mmm(t3);
    ep = sun(m, d, 18.5, lg);
    zr = ep[0];
    delta = ep[1];
    ha = loc2hor(94.3, delta, lat);
    var t4 = Round(zr + ha, 24);
    ep = sun(m, d, t4, lg);
    zr = ep[0];
    delta = ep[1];
    ha = loc2hor(94.3, delta, lat);
    t4 = Round(zr + ha + 0.013, 24);
    document.getElementById("azan_t5").innerHTML = hms(t4);
    document.getElementById("azan_ht5").value = hhh(t4);
    document.getElementById("azan_mt5").value = mmm(t4);
    preShowNow($imgOghat1, $imgOghat2, $timeUrl);
}
function sun(m, d, h, lg) {
    if (m < 7) {
        JAT = 1;
        d = 31 * (m - 1) + d + h / 24;
    } else {
        JAT = 0;
        d = 6 + 30 * (m - 1) + d + h / 24;
    }
    var M = 74.2023 + 0.98560026 * d;
    var L = -2.75043 + 0.98564735 * d;
    var lst = 8.3162159 + 0.065709824 * Math.floor(d) + 1.00273791 * 24 * (d % 1) + lg / 15;
    var e = 0.0167065;
    var omega = 4.85131 - 0.052954 * d;
    var ep = 23.4384717 + 0.00256 * cosd(omega);
    var ed = 180.0 / Math.PI * e;
    var u = M;
    for (var i = 1; i < 5; i++)
        u = u - (u - ed * sind(u) - M) / (1 - e * cosd(u));
    var v = 2 * atand(tand(u / 2) * Math.sqrt((1 + e) / (1 - e)));
    var theta = L + v - M - 0.00569 - 0.00479 * sind(omega);
    var delta = asind(sind(ep) * sind(theta));
    var alpha = 180.0 / Math.PI * Math.atan2(cosd(ep) * sind(theta), cosd(theta));
    if (alpha >= 360)
        alpha -= 360;
    var ha = lst - alpha / 15;
    var zr = Round(h - ha, 24);
    return ([zr, delta])
}
function sind(x) {
    return (Math.sin(Math.PI / 180.0 * x));
}
function cosd(x) {
    return (Math.cos(Math.PI / 180.0 * x));
}
function tand(x) {
    return (Math.tan(Math.PI / 180.0 * x));
}
function atand(x) {
    return (Math.atan(x) * 180.0 / Math.PI);
}
function asind(x) {
    return (Math.asin(x) * 180.0 / Math.PI);
}
function acosd(x) {
    return (Math.acos(x) * 180.0 / Math.PI);
}
function sqrt(x) {
    return (Math.sqrt(x));
}
function frac(x) {
    return (x % 1);
}
function floor(x) {
    return (Math.floor(x));
}
function ceil(x) {
    return (Math.ceil(x));
}
function loc2hor(z, d, p) {
    return (acosd((cosd(z) - sind(d) * sind(p)) / cosd(d) / cosd(p)) / 15);
}
function Round(x, a) {
    var tmp = x % a;
    if (tmp < 0)
        tmp += a;
    return (tmp)
}
function hms(x) {
    x = Math.floor(3600 * x);
    h = Math.floor(x / 3600) + JAT;
    mp = x - 3600 * h;
    m = Math.floor(mp / 60) + (JAT * 60);
    s = Math.floor(mp - 60 * m) + (JAT * 3600);
    return (((h < 10) ? "0" : "") + h.toString() + ":" + ((m < 10) ? "0" : "") + m.toString() + ":" + ((s < 10) ? "0" : "") + s.toString())
}
function hhh(x) {
    x = Math.floor(3600 * x);
    h = Math.floor(x / 3600) + JAT;
    mp = x - 3600 * h;
    m = Math.floor(mp / 60);
    s = Math.floor(mp - 60 * m);
    return (((h < 10) ? "0" : "") + h.toString())
}
function mmm(x) {
    x = Math.floor(3600 * x);
    h = Math.floor(x / 3600);
    mp = x - 3600 * h;
    m = Math.floor(mp / 60);
    s = Math.floor(mp - 60 * m);
    return (((m < 10) ? "0" : "") + m.toString())
}
function offshownow($imgOghat2) {
    document.getElementById("azan_p1").src = $imgOghat2;
    document.getElementById("azan_p2").src = $imgOghat2;
    document.getElementById("azan_p3").src = $imgOghat2;
    document.getElementById("azan_p4").src = $imgOghat2;
    document.getElementById("azan_p5").src = $imgOghat2;
}
var offset;
var azanOffset;
function preShowNow($imgOghat1, $imgOghat2, $timeUrl) {
    if (offset === undefined ){
        var azan = [document.getElementById("azan_ht1").value , document.getElementById("azan_mt1").value];
        $.ajax({
            url: $timeUrl,
            type: "POST",
            data: { "azan": JSON.stringify(azan)},
            dataType: "json",
            success : function(data) {
                if ( data ) {
                    offset = data.time*1000 - (new Date()).getTime();
                    var signOffset = 1;
                    if (offset < 0)
                        signOffset = -1;
                    if (Math.abs(offset) >= (1000* 60))
                        offset = Math.floor(Math.abs(offset) / (1000* 60)) * (1000* 60)*signOffset;
                    else
                        offset = 0;
                    azanT = new Date();
                    azanT.setHours(azan[0]);
                    azanT.setMinutes(azan[1]);
                    azanOffset = data.azanTime*1000 - azanT.getTime();
                    var signAzanOffset = 1;
                    if (azanOffset < 0)
                        signAzanOffset = -1;
                    if (Math.abs(azanOffset) >= (1000* 60))
                        azanOffset = Math.floor(Math.abs(azanOffset) / (1000* 60)) * (1000* 60)*signAzanOffset;
                    else
                        azanOffset = 0;
                    showNow($imgOghat1, $imgOghat2);
                }
            }
        });
    }
    else{
        showNow($imgOghat1, $imgOghat2);
    }
}
function showNow($imgOghat1, $imgOghat2 ) {
    today = new Date();
    azan_ttt = new Date();
    azan_ttt.setHours(document.getElementById("azan_ht1").value);
    azan_ttt.setMinutes(document.getElementById("azan_mt1").value);
    if (azan_ttt.getTime()+azanOffset > today.getTime()+offset) {
        offshownow($imgOghat2);
        document.getElementById("azan_p1").src = $imgOghat1;
        diff = azan_ttt.getTime()+azanOffset - today.getTime()+offset;
        diff = Math.floor(diff / (1000 * 60));
        hh = Math.floor(diff / (60));
        ss = diff - (hh * 60);
        document.getElementById("azanazan").innerHTML = "<font color=#" + cl + ">" + hh + "</font><font color=#" + cl + " id=donokh>:</font><font color=#" + cl + ">" + ss + "</font> " + OW.getLanguageText('iisoghat', 'until') + " <font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'Azan_am')+"</font>";
        donokh_show();
    }
    else {
        if (azan_ttt.getTime()+azanOffset == today.getTime()+offset) {
            document.getElementById('displ').style.display = 'block';
            offshownow($imgOghat2);
            document.getElementById("azan_p1").src = $imgOghat1;
            document.getElementById("azanazan").innerHTML = "<font color=#" + cl + " id=donokh></font><font color=#" + cl + ">" + OW.getLanguageText('iisoghat', 'Azan_am_time_horizon') + " " + document.getElementById("cities").innerHTML + "</font>";
        }
        else {
            azan_ttt = new Date();
            azan_ttt.setHours(document.getElementById("azan_ht2").value);
            azan_ttt.setMinutes(document.getElementById("azan_mt2").value);
            if (azan_ttt.getTime()+azanOffset > today.getTime()+offset) {
                offshownow($imgOghat2);
                document.getElementById("azan_p2").src = $imgOghat1;
                diff = azan_ttt.getTime()+azanOffset - today.getTime()+offset;
                diff = Math.floor(diff / (1000 * 60));
                hh = Math.floor(diff / (60));
                ss = diff - (hh * 60);
                document.getElementById("azanazan").innerHTML = "<font color=#" + cl + ">" + hh + "</font><font color=#" + cl + " id=donokh>:</font><font color=#" + cl + ">" + ss + "</font> "+OW.getLanguageText('iisoghat', 'until')+" <font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'Azan_pm')+"</font>";
                donokh_show();
            }
            else {
                if (azan_ttt.getTime()+azanOffset == today.getTime()+offset) {
                    offshownow($imgOghat2);
                    document.getElementById("azan_p2").src = $imgOghat1;
                    document.getElementById("azanazan").innerHTML = "<font color=#" + cl + " id=donokh></font><font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'Sunrise')+"</font>";
                }
                else {
                    azan_ttt = new Date();
                    azan_ttt.setHours(document.getElementById("azan_ht3").value);
                    azan_ttt.setMinutes(document.getElementById("azan_mt3").value);
                    if (azan_ttt.getTime()+azanOffset > today.getTime()+offset) {
                        offshownow($imgOghat2);
                        document.getElementById("azan_p3").src = $imgOghat1;
                        diff = azan_ttt.getTime()+azanOffset - today.getTime()+offset;
                        diff = Math.floor(diff / (1000 * 60));
                        hh = Math.floor(diff / (60));
                        ss = diff - (hh * 60);
                        document.getElementById("azanazan").innerHTML = "<font color=#" + cl + ">" + hh + "</font><font color=#" + cl + " id=donokh>:</font><font color=#" + cl + ">" + ss + "</font> "+OW.getLanguageText('iisoghat', 'until')+" <font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'Azan_pm')+"</font>";
                        donokh_show();
                    }
                    else {
                        if (azan_ttt.getTime()+azanOffset == today.getTime()+offset) {
                            document.getElementById('displ').style.display = 'block';
                            offshownow($imgOghat2);
                            document.getElementById("azan_p3").src = $imgOghat1;
                            document.getElementById("azanazan").innerHTML = "<font color=#" + cl + " id=donokh></font><font color=#" + cl + ">" + OW.getLanguageText('iisoghat', 'Azan_pm_time_horizon') + " " + document.getElementById("cities").innerHTML + "</font>";
                        }
                        else {
                            azan_ttt = new Date();
                            azan_ttt.setHours(document.getElementById("azan_ht4").value);
                            azan_ttt.setMinutes(document.getElementById("azan_mt4").value);
                            if (azan_ttt.getTime()+azanOffset > today.getTime()+offset) {
                                offshownow($imgOghat2);
                                document.getElementById("azan_p4").src = $imgOghat1;
                                diff = azan_ttt.getTime()+azanOffset - today.getTime()+offset;
                                diff = Math.floor(diff / (1000 * 60));
                                hh = Math.floor(diff / (60));
                                ss = diff - (hh * 60);
                                document.getElementById("azanazan").innerHTML = "<font color=#" + cl + ">" + hh + "</font><font color=#" + cl + " id=donokh>:</font><font color=#" + cl + ">" + ss + "</font> "+OW.getLanguageText('iisoghat', 'until')+" <font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'Sunset')+"</font>";
                                donokh_show();
                            }
                            else {
                                if (azan_ttt.getTime()+azanOffset == today.getTime()+offset) {
                                    offshownow($imgOghat2);
                                    document.getElementById("azan_p4").src = $imgOghat1;
                                    document.getElementById("azanazan").innerHTML = "<font color=#" + cl + " id=donokh></font><font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'Sunset')+"</font>";
                                }
                                else {
                                    azan_ttt = new Date();
                                    azan_ttt.setHours(document.getElementById("azan_ht5").value);
                                    azan_ttt.setMinutes(document.getElementById("azan_mt5").value);
                                    if (azan_ttt.getTime()+azanOffset > today.getTime()+offset) {
                                        offshownow($imgOghat2);
                                        document.getElementById("azan_p5").src = $imgOghat1;
                                        diff = azan_ttt.getTime()+azanOffset - today.getTime()+offset;
                                        diff = Math.floor(diff / (1000*60));
                                        hh = Math.floor(diff / (60));
                                        ss = diff - (hh * 60);
                                        document.getElementById("azanazan").innerHTML = "<font color=#" + cl + ">" + hh + "</font><font color=#" + cl + " id=donokh>:</font><font color=#" + cl + ">" + ss + "</font> "+OW.getLanguageText('iisoghat', 'until')+" <font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'azan_maghreb')+"</font>";
                                        donokh_show();
                                    }
                                    else {
                                        if (azan_ttt.getTime()+azanOffset == today.getTime()+offset) {
                                            document.getElementById('displ').style.display = 'block';
                                            offshownow($imgOghat2);
                                            document.getElementById("azan_p5").src = $imgOghat1;
                                            document.getElementById("azanazan").innerHTML = "<font color=#" + cl + " id=donokh></font><font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'azan_maghreb_time_horizon') + " " + document.getElementById("cities").innerHTML + "</font>";
                                            document.getElementById("azann").src = "<embed src='Moazenzadeh.mp3' autostart='true' hidden='false' loop='false'>";
                                        }
                                        else {
                                            azan_ttt = new Date();
                                            azan_ttt.setHours(23);
                                            azan_ttt.setMinutes(59);
                                            diff = azan_ttt.getTime()+azanOffset - today.getTime()+offset;
                                            diff = Math.floor(diff / (1000 * 60));
                                            hh = Math.floor(diff / (60));
                                            ss = diff - (hh * 60);

                                            offshownow($imgOghat2);
                                            document.getElementById("azan_p1").src = $imgOghat1;
                                            hh += Math.floor(document.getElementById("azan_ht1").value);
                                            ss += Math.floor(document.getElementById("azan_mt1").value);
                                            if (ss > 59){
                                                hh += 1;
                                                ss -= 60;
                                            }
                                            document.getElementById("azanazan").innerHTML = "<font color=#" + cl + ">" + hh + "</font><font color=#" + cl + " id=donokh>:</font><font color=#" + cl + ">" + ss + "</font> "+OW.getLanguageText('iisoghat', 'until')+" <font color=#" + cl + ">"+OW.getLanguageText('iisoghat', 'Azan_am')+"</font>";
                                            donokh_show();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
function donokh_show() {
    document.getElementById("donokh").color = "#0000ff"
    setTimeout("donokh_hide()", 1000);
}
function donokh_hide() {
    document.getElementById("donokh").color = "#FFFFFF"
    setTimeout("donokh_show()", 1000);
}

function changeCity($imgOghat1, $imgOghat2,$timeUrl){
    $cityOptions = document.getElementById('citiesOption');
    var selectedCity = $cityOptions.selectedOptions[0];
    coord(selectedCity.attributes['longitude'].value, selectedCity.attributes['latitude'].value);
    cityChanged = true;
    main_oghat(selectedCity.value,$imgOghat1, $imgOghat2,$timeUrl);
}

cl = "226699"; // range bold