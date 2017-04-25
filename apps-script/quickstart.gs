// Note: Apps Script automatically requests authorization
// based on the API's used in the code.

function channelsListByUsername(part, params) {
  var response = YouTube.Channels.list(part,
                                       params);
  var channel = response.items[0];
  var dataRow = [channel.id, channel.snippet.title, channel.statistics.viewCount];
  SpreadsheetApp.getActiveSpreadsheet().appendRow(dataRow);
}

function getChannel() {
  var ui = SpreadsheetApp.getUi();
  var channelName = ui.prompt("Enter the channel name: ").getResponseText();
  channelsListByUsername('snippet,contentDetails,statistics',
                         {'forUsername': channelName});
}

function getGoogleDevelopersChannel() {
  channelsListByUsername('snippet,contentDetails,statistics',
                         {'forUsername': 'GoogleDevelopers'});
}

function onOpen() {
  var firstCell = SpreadsheetApp.getActiveSheet().getRange(1, 1).getValue();
  if (firstCell != 'ID') {
    var headerRow = ["ID", "Title", "View count"];
    SpreadsheetApp.getActiveSpreadsheet().appendRow(headerRow);
  }
  var ui = SpreadsheetApp.getUi();
  ui.createMenu('YouTube Data')
  .addItem('Add channel data', 'getChannel')
  .addSeparator()
  .addItem('Add GoogleDevelopers data', 'getGoogleDevelopersChannel')
  .addToUi();
}
