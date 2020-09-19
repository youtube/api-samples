const fs = require('fs/promises');
const readline = require('readline');
const {google} = require('googleapis');
const OAuth2 = google.auth.OAuth2;

async function readJSON(filename) {
    const content = await fs.readFile(filename);
    return JSON.parse(content);
}

async function writeJSON(filename, data) {
    const content = JSON.stringify(data);
    await fs.writeFile(filename, content);
}

async function oauth(oauth2Client) {
    const authUrl = oauth2Client.generateAuthUrl({
        access_type: 'offline',
        scope: ['https://www.googleapis.com/auth/youtube'],
    });
    const code = await askCode(authUrl);
    return await oauth2Client.getToken(code);
}

function askCode(authUrl) {
    const rlp = readline.createInterface({
        input: process.stdin,
        output: process.stdout,
        terminal: true
    });

    return new Promise((resolve, reject) => {
        console.log('Authorize this app by visiting this url: ', authUrl);
        const code = rlp.question('Than enter the code from that page here: ', code => {
            rlp.close();
            resolve(code);
        });
    });
}

async function getAndStoreNewToken(oauth2Client, filename) {
    const token = await oauth(oauth2Client);
    await writeJSON(filename, token);
    return token;
}

async function getExistingOrNewToken(oauth2Client, filename) {
    try {
        return await readJSON(filename);
    } catch (err) {
        return await getAndStoreNewToken(oauth2Client, filename);
    }
}

async function createOAuth2ClientWithToken(credentialsFilename, tokenFilename) {
    const credentials = await readJSON(credentialsFilename);
    const oauth2Client = new OAuth2(
        credentials.installed.client_id,
        credentials.installed.client_secret,
        credentials.installed.redirect_uris[0],
    );

    const {tokens} = await getExistingOrNewToken(oauth2Client, tokenFilename);
    oauth2Client.setCredentials(tokens);

    return oauth2Client;
}

async function getLikes() {
    const oauth2Client = await createOAuth2ClientWithToken('client_secret.json', '.token');
    const ytApi = google.youtube('v3');

    const res = await ytApi.videos.list({
        auth: oauth2Client,
        part: 'id',
        myRating: 'like',
        maxResults: 5,
    });

    const videos = res.data.items;
    videos.forEach(video => console.log('You liked', video.id));
}

getLike();
