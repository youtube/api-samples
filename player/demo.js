var player;
// Callback for when the YouTube iFrame player is ready
function onYouTubeIframeAPIReady() {
  player = new YT.Player('player', {
    // Set Player height and width
    height: '390',
    width: '640',
    // Set the id of the video to be played
    videoId: 'M7lc1UVf-VE',
    // Setup event handelers
    events: {
      'onReady': onPlayerReady,
      'onStateChange': onPlayerStateChange,
      'onPlaybackQualityChange': onPlaybackQualityChange,
      'onPlaybackRateChange': onPlaybackRateChange,
      'onError': onError,
      'onApiChange': onApiChange,
    }
  });
};

// Event Handlers 
function onPlaybackQualityChange(){
  // Update playback quality on page
  update("quality");
};
function onPlaybackRateChange(){
  // Update playback rate on page
  update("rate");
};
function onError(error){
  // Update errors on page
  console.log("Error!");
};
function onApiChange(event){
  // Update currently availbe APIs
  console.log("API Change!");
};
function onPlayerReady(){
  // Update page after player is ready
  updateAll();
  playVideo();
}

function onPlayerStateChange(event){
  // Get current state
  // Video has ended
  switch (event.data) {
    case YT.PlayerState.ENDED:
      updateAll() // set status for state, ...
      clearIntervals() // clear all intervals
      break;
    case YT.PlayerState.PLAYING:
      updateAll() // set status for state, ...
      setIntervals() // set intervals for ...
      break;
    case YT.PlayerState.PAUSED:
      updateAll() // set status for state, ...
      clearIntervals() // clear all intervals
      break;
    case YT.PlayerState.BUFFERING:
      updateAll() // set status for state, ...
      clearIntervals() // clear all intervals
      break;
    case YT.PlayerState.CUED:
      updateAll() // set status for state, ...
      clearIntervals() // clear all intervals
      break;
    default:
      updateAll() // set status for state, ...
      clearIntervals() // clear all intervals
      break;

  }
};

// Update HTML nodes on the page
// with most recent values from
// the YouTube iFrame API
function update(node){
  switch (node){
    // Update player reported changes
    case "duration":
      document.getElementById("duration").innerHTML = player.getDuration()+"s";
      break;
    case "url":
      var url = player.getVideoUrl();
      document.getElementById("url").innerHTML = "<a href=\""+url+"\" target=\"_blank\">"+url+"</a>";
      break;
    case "embedCode":
      var embedCode = player.getVideoEmbedCode();
      var index = Math.ceil(embedCode.length/3);
      var fmtEmbedCode = [embedCode.slice(0, index), "\n", embedCode.slice(index, index*2),"\n", embedCode.slice(index*2)].join('');
      document.getElementById("embedCode").innerText = fmtEmbedCode
      break;
    case "percentLoaded":
      document.getElementById("percentLoaded").innerHTML = player.getVideoLoadedFraction()*100+"%"
      break;
    case "status":
      var state = player.getPlayerState()
      switch (state){
        case YT.PlayerState.ENDED:
          status="ENDED";
          break;
        case YT.PlayerState.PLAYING:   
          status="PLAYING";
          break;
        case YT.PlayerState.PAUSED:    
          status="PAUSED";
          break;
        case YT.PlayerState.BUFFERING: 
          status="BUFFERING";
          break;
        case YT.PlayerState.CUED:      
          status="CUED";
          break;
        default:
          status="UNKNOWN";
          break;
      }
      document.getElementById("status").innerHTML = status+" ("+state+")";
      break;
    case "currentTime":
      document.getElementById("currentTime").innerHTML = player.getCurrentTime()+"s"
      break;
    case "volume":
      document.getElementById("volume").innerHTML = player.getVolume()
      break;
    case "mute":
      document.getElementById("mute").innerHTML = player.isMuted()
      break;
    case "quality":
      var availableQualityLevels = player.getAvailableQualityLevels()
      var selectbox = document.getElementById('qualityOption');
      //clear existing options
      var i;
      for(i=selectbox.options.length-1;i>=0;i--){
          selectbox.remove(i);
      }
      //write current available options
      for (var i in availableQualityLevels){
        var opt = document.createElement("OPTION");
        opt.text = availableQualityLevels[i];
        opt.value = availableQualityLevels[i];
        selectbox.options.add(opt);
      }
      document.getElementById("quality").innerHTML = player.getPlaybackQuality()
      break;
    case "rate":
      var availableRates = player.getAvailablePlaybackRates()
      var selectbox = document.getElementById('rateOption');
      //clear existing options
      var i;
      for(i=selectbox.options.length-1;i>=0;i--){
          selectbox.remove(i);
      }
      //write current available options
      for (var i in availableRates){
        var opt = document.createElement("OPTION");
        opt.text = availableRates[i];
        opt.value = availableRates[i];
        selectbox.options.add(opt);
      }
      document.getElementById("rate").innerHTML = player.getPlaybackRate()
      break;
    case "title":
      document.getElementById("title").innerHTML = player.getVideoData()["title"]
      break;
    case "author":
      document.getElementById("author").innerHTML = player.getVideoData()["author"]
      break;
    case "video_id":
      document.getElementById("video_id").innerHTML = player.getVideoData()["video_id"]
      break;
  }
};
// Updates all HTML nodes
function updateAll(){
  for (var node in nodeList){
    update(nodeList[node]);
  }
};
// Array to track all HTML nodes
var nodeList = [
  "duration",
  "url",
  "embedCode",
  "percentLoaded",
  "status",
  "currentTime",
  "volume",
  "mute",
  "quality",
  "rate",
  "title",
  "author",
  "video_id",
];

// Functions to invoke user requested action through the iFrame API
function loadNewVideo(){
  player.loadVideoById(document.getElementById("video_idOption").value);
};
function cueNewVideo(){
  player.cueVideoById(document.getElementById("video_idOption").value);
};
function playVideo(){
  player.playVideo();
};
function pauseVideo(){
  player.pauseVideo();
};
function stopVideo(){
  player.stopVideo();
};
function seekTo(){
  player.seekTo(document.getElementById("currentTimeOption").value);  
};
function setVolume(){
  player.setVolume(document.getElementById("volumeOption").value);  
};
function mute(){
  player.mute();
};
function unmute(){
  player.unMute();  
};
function setQuality(){
  player.setPlaybackQuality(document.getElementById("qualityOption").value);  
};
function setRate(){
  player.setPlaybackRate(document.getElementById("rateOption").value);  
};

// Controls interval handlers to update page contens
// Array to track intervals
var activeIntervals = [];
function setIntervals(){
  // Sets invertval funtions to actively update page content
  activeIntervals[0] = setInterval(function(){update("percentLoaded")}, 500);
  activeIntervals[1] = setInterval(function(){update("currentTime")}, 500);
  activeIntervals[2] = setInterval(function(){update("mute")}, 500);
  activeIntervals[3] = setInterval(function(){update("volume")}, 500);
};
function clearIntervals(){
  // Clears existing intervals to actively update page content
  for (var interval in activeIntervals){
    clearInterval(interval);
  }
};