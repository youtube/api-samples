var player;
// Callback for when the YouTube iFrame player is ready
function onYouTubeIframeAPIReady() {
  player = new YT.Player('player', {
    // Set Player height and width
    height: '390',
    width: '640',
    // Set the id of the video to be played
    videoId: 'M7lc1UVf-VE',
    events: {
      'onReady': onPlayerReady
      // You can add more event listeners here
    }
  });
};

function onPlayerReady (){
  //add onStateChange event handler
  player.addEventListener("onStateChange","onPlayerStateChange");

  //add your own rate listener below:

};

function onPlayerStateChange(event){
  // Get current state
  var currentState;
  if (event.data == YT.PlayerState.ENDED){
    currentState = "Ended";
  } 
  else if (event.data == YT.PlayerState.PLAYING){
    currentState = "Playing";
  } 
  else if (event.data == YT.PlayerState.PAUSED){
    currentState = "Paused";
  }  
  else if (event.data == YT.PlayerState.BUFFERING){
    currentState = "Buffering";
  }
  else if (event.data == YT.PlayerState.CUED){
    currentState = "Cued";
  } else{
    currentState = "Unknown";
  }

  currentState += " (" + event.data + ")"
  // Update video state div
  document.getElementById('currentState').innerText = currentState;
};

function onPlaybackRateChange(event){
  // Implment this function to display the rate of the player on the page
  var currentRate;

  // You code goes here

  document.getElementById('currentRate').innerText = currentRate;  
}