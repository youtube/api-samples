### [Set and retrieve localized playlist metadata](/python/playlist_localizations.py)

Method: youtube.playlists.update, youtube.playlists.list<br>
Description: This sample demonstrates how to use the following API methods to set and retrieve localized metadata for a
playlist:<br>
<ul>
<li>It calls the <code>playlists.update</code> method to update the default language of a playlist's metadata and to add
a localized version of this metadata in a selected language.</li>
<li>It calls the <code>playlists.list</code> method with the <code>hl</code> parameter set to a specific language to
retrieve localized metadata in that language.</li>
<li>It calls the <code>playlists.list</code> method and includes <code>localizations</code> in the <code>part</code>
parameter value to retrieve all of the localized metadata for that playlist.</li>
</ul>
