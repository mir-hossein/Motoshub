/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismention
 * @since 1.0
 */

var iismentionListCache = {};
function iismention_wait_for_at() {
    var text_input_selector = '#newsfeed_status_input,textarea[name=commentText]';
    setInterval(function() {

        $(text_input_selector).each(function() {
            if($(this).attr('mention-loaded')!=='y'){
                $(this).attr('mention-loaded', 'y');

                var settings_data = function (q) {
                    if (q.length < 3) return [];
                    if (q in iismentionListCache)
                        return iismentionListCache[q];
                    var ret = $.getJSON(mentionLoadUsernamesUrl + q);
                    iismentionListCache[q] = ret;
                    return ret;
                };
                var settings_map = function (user) {
                    return {
                        value: user.username,
                        text: '<strong>' + user.username + '</strong> <small>' + user.fullname + '</small>'
                    }
                };

                $('textarea[name=commentText]').suggest_mention('@', {data: settings_data,map: settings_map,position: "mobile_top"});
                $('#newsfeed_status_input').suggest_mention('@', {data: settings_data,map: settings_map,position: "mobile_bottom"});
            }
        });

    }, 1000);
}

$(function() {
    iismention_wait_for_at();
});