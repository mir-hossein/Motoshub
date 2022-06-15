<?php
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @since 1.0
 */
if ( OW::getConfig()->configExists('newsfeed', 'disabled_action_types')){
    OW::getConfig()->saveConfig('newsfeed', 'disabled_action_types', '{"create:user_join":[true,true,true],"create:user_edit":[false,false,true],"create:avatar-change":[false,false,false],"create:user-comment":[true,true,true],"*:friend_add":[true,false,true],"*:photo_comments,*:multiple_photo_upload":[true,true,false],"*:video_comments":[true,true,false],"create:forum-topic":[true,true,false],"forum-post:forum-topic":[true,true,false],"*:group":[true,true,false],"*:blog-post":[true,true,false],"*:event":[true,true,false],"*:news-entry":[true,true,false],"*:birthday":[true,true,false]}');
}else{
    OW::getConfig()->addConfig('newsfeed', 'disabled_action_types', '{"create:user_join":[true,true,true],"create:user_edit":[false,false,true],"create:avatar-change":[false,false,false],"create:user-comment":[true,true,true],"*:friend_add":[true,false,true],"*:photo_comments,*:multiple_photo_upload":[true,true,false],"*:video_comments":[true,true,false],"create:forum-topic":[true,true,false],"forum-post:forum-topic":[true,true,false],"*:group":[true,true,false],"*:blog-post":[true,true,false],"*:event":[true,true,false],"*:news-entry":[true,true,false],"*:birthday":[true,true,false]}');
}
