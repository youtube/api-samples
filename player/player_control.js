var player;
// Callback for when the YouTube iFrame player is ready
function onYouTubeIframeAPIReady() {
  player = new YT.Player('player', {
    // Set Player height and width
    height: '390',
    width: '640',
    // Set the id of the video to be played
    videoId: 'M7lc1UVf-VE',
    // Setup event listeners
    // These are covered in the next section
    events: {
      'onReady': onPlayerReady
    }
  });
};

function onPlayerReady (){
  player.playVideo();
  setTimeout(pauseVideo, 4000);
  setTimeout(loadNewVideo, 6000);
  setTimeout(stopVideo, 8500);
  setInterval(getContent, 1000);
};

function pauseVideo(){
  player.pauseVideo();
}

function loadNewVideo(){
  player.loadVideoById("me91AGSVsyo");
}

function stopVideo(){
  player.stopVideo();
}

function getContent(){
  var content
  content = player.INSERT_METHOD_HERE;
  document.getElementById('content').innerText = content
}