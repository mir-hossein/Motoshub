// When user chooses a MP4 file
document.querySelector("#videoUpload").addEventListener('change', function() {
    // Validate whether MP4
    if (typeof document.querySelector("#videoUpload").files[0] === "undefined") {
        return;
    }
    if(['video/mp4'].indexOf(document.querySelector("#videoUpload").files[0].type) == -1) {
        $.alert(OW.getLanguageText('iisvideoplus', 'upload_file_extension_is_not_allowed'));
        return;
    }

    // Object Url as the video source
    document.querySelector("#main-video source").setAttribute('src', URL.createObjectURL(document.querySelector("#videoUpload").files[0]));

    // Load the video and show it
    _VIDEO = document.querySelector("#main-video");
    _VIDEO.load();
    _VIDEO.style.display = 'inline';

    // Load metadata of the video to get video duration and dimensions
    _VIDEO.addEventListener('loadedmetadata', function() { console.log(_VIDEO.duration);
        var video_duration = _VIDEO.duration,
            duration_options_html = '',
            step = 8;
        if(Math.floor(video_duration)/10>8)
        {
            step = Math.floor(video_duration)/10;
        }
        // Set options in dropdown at ten intervals
        for(var i=0; i<Math.floor(video_duration); i=i+step) {
            duration_options_html += '<option value="' + i + '">' + i + '</option>';
        }
        document.querySelector("#set-video-seconds").innerHTML = duration_options_html;

        // Show the dropdown container
        document.querySelector("#thumbnail-container").style.display = 'block';

        $("#videoUploadThumbnail").val("");
        createThumbnail();
    });
});


document.querySelector("#set-video-seconds").addEventListener('change', function() {
    _VIDEO = document.querySelector("#main-video");
    _VIDEO.currentTime = document.querySelector("#set-video-seconds").value;
    // Seeking might take a few milliseconds, so disable the dropdown and hide download link
    document.querySelector("#set-video-seconds").disabled = true;
    $("#videoUploadThumbnail").val("");
});


document.querySelector("#main-video").addEventListener('timeupdate', function() {
    if(!$("#videoUploadThumbnail").val()) {
        createThumbnail();
    }
});

function createThumbnail()
{
    var videoObject = document.querySelector("#main-video");
    var canvas =  document.querySelector("#video-canvas");
    canvas.width = videoObject.videoWidth;
    canvas.height = videoObject.videoHeight;
    canvas.getContext('2d').drawImage(videoObject, 0, 0, canvas.width, canvas.height);
    canvasData = canvas.toDataURL("image/png");
    document.querySelector("#set-video-seconds").disabled = false;
    document.getElementById('videoUploadThumbnail').value=canvasData;
};

