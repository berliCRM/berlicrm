// Real Firefox, isolated temporary profile, synthetic CRM/Microsoft replies only.
// Run explicitly: node outlook-addin/tests/firefox-contact-launch.cjs
// Requires geckodriver on PATH or BERLI_GECKODRIVER pointing to Mozilla's driver.
const fs = require('node:fs/promises');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const http = require('node:http');
const crypto = require('node:crypto');
const { spawn } = require('node:child_process');
const { once } = require('node:events');
const assert = require('node:assert/strict');
const delay = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));
async function freePort() {
  const server = net.createServer(); server.listen(0, '127.0.0.1'); await once(server, 'listening');
  const port = server.address().port; await new Promise(resolve => server.close(resolve)); return port;
}
async function main() {
  const binary = process.env.BERLI_FIREFOX_BINARY || 'C:/Program Files/Mozilla Firefox/firefox.exe';
  await fs.access(binary);
  const profile = await fs.mkdtemp(path.join(os.tmpdir(), 'berli-firefox-auth-'));
  const root = path.resolve(__dirname, '..'), flows = new Map();
  const port = await freePort(), controlPort = await freePort();
  const crmOrigin = 'http://localhost:' + port;
  const metadata = { ownerId: '19x3', propertyId: 'fixture', canCreate: true, fields: [] };
  let completed = false, geckodriver, sessionId;
  let settingsHadNoOpener = false, settingsGraphReads = 0, contactReads = 0, contactWrites = 0;
  const driver = async (method, route, body) => {
    const response = await fetch('http://127.0.0.1:' + controlPort + route, {
      method, headers: { 'Content-Type': 'application/json' }, ...(body ? { body: JSON.stringify(body) } : {}),
      signal: AbortSignal.timeout(20000),
    });
    const result = await response.json();
    if (!response.ok || result.value?.error) throw new Error(route + ': ' + JSON.stringify(result.value));
    return result.value;
  };
  const server = http.createServer(async (request, response) => {
    try {
      const url = new URL(request.url, crmOrigin);
      const pagePath = url.pathname.replace(/^\/outlook-addin(?=\/)/, '');
      const send = (text, type = 'text/html') => { response.writeHead(200, { 'Content-Type': type + '; charset=utf-8' }); response.end(text); };
      if (pagePath === '/pane-graph') {
        assert.equal(request.method, 'GET'); contactReads++;
        assert.equal(request.headers.authorization, 'Bearer synthetic-not-a-real-token');
        return send(JSON.stringify({ value: [{ id: 'fixture-contact', givenName: 'Outlook', surname: 'Fixture',
          displayName: 'Outlook Fixture', emailAddresses: [{ address: 'outlook@example.test' }] }] }), 'application/json');
      }
      if (pagePath === '/assets/icon-80.png') {
        response.writeHead(200, { 'Content-Type': 'image/png' }); return response.end(await fs.readFile(path.join(root, 'assets/icon-80.png')));
      }
      if (pagePath === '/settings') return send(`<!doctype html><div id="outlookConnectionCheck">
        <button id="outlookCheckConnection">Check Microsoft connection</button><span id="outlookCancelConnection" hidden>Cancel</span>
        <p id="outlookConnectionStatus" hidden></p></div><script>
        Object.assign(document.getElementById('outlookConnectionCheck').dataset,${JSON.stringify({
          authUrl: crmOrigin + '/outlook-addin/contacts-auth.html', language: 'en_us',
          signingIn: 'Signing in', reading: 'Reading', success: 'Read access succeeded', failed: 'Failed:',
          badReply: 'Invalid reply', blocked: 'Blocked', closed: 'Closed', cancelled: 'Cancelled', timedOut: 'Timeout',
          denied: 'Denied', httpError: 'HTTP error',
        })});
        const nativeFetch=window.fetch.bind(window);window.fetch=(url,init)=>nativeFetch(
          String(url).startsWith('https://graph.microsoft.com/')?'/settings-graph':url,init);
        </script><script src="settings-connection.js"></script>`);
      if (pagePath === '/settings-isolate') {
        response.setHeader('Cross-Origin-Opener-Policy', 'same-origin');
        const callback = new URL(url.searchParams.get('returnUrl'), crmOrigin);
        assert.equal(callback.origin, crmOrigin); assert.equal(callback.pathname, '/outlook-addin/contacts-auth.html');
        callback.searchParams.set('settingsCallback', '1');
        return send('<script>setTimeout(()=>location.replace(' + JSON.stringify(callback.href) + '),1000);</script>');
      }
      if (pagePath === '/settings-probe') { settingsHadNoOpener = url.searchParams.get('opener') === 'false'; return send('ok'); }
      if (pagePath === '/settings-graph') {
        assert.equal(request.method, 'GET');
        assert.equal(request.headers.authorization, 'Bearer synthetic-settings-token');
        settingsGraphReads++; return send(JSON.stringify({ value: [] }), 'application/json');
      }
      if (pagePath === '/settings-auth-fixture.js') {
        const source = (await fs.readFile(path.join(root, 'contacts-auth-entry.js'), 'utf8')).replace(/^import .*;\r?\n/gm, '');
        return send(`class PublicClientApplication {
          async initialize(){} async clearCache(){}
          async handleRedirectPromise(){
            if(!new URLSearchParams(location.search).has('settingsCallback'))return null;
            await fetch('/settings-probe?opener='+Boolean(window.opener));
            return {accessToken:'synthetic-settings-token',account:{homeAccountId:'fixture',username:'fixture@example.test'},expiresOn:new Date(Date.now()+60000)};
          }
          async loginRedirect(){
            const target=new URL(${JSON.stringify('http://127.0.0.1:' + port + '/settings-isolate')});
            target.searchParams.set('returnUrl',location.href);location.replace(target.href);await new Promise(()=>{});
          }
        }
        function broadcastResponseToMainFrame(){return Promise.resolve();}
        ` + source, 'application/javascript');
      }
      if (pagePath === '/contacts-auth.html' && (url.searchParams.get('source') === 'settings' || url.searchParams.has('settingsCallback'))) {
        const html = (await fs.readFile(path.join(root, 'contacts-auth.html'), 'utf8')).replace(
          /<script src="assets\/contacts-auth.bundle.js[^>]*><\/script>/,
          '<script src="settings-auth-fixture.js"></script>');
        return send(html);
      }
      if (url.pathname === '/outlook') return send('<style>body{margin:0}iframe{width:100%;height:98vh;border:0}</style><iframe id="crm" src="' + crmOrigin + '/taskpane.html"></iframe>');
      if (url.pathname === '/taskpane.html') return send(`<!doctype html><a id="launch" href="contacts.html">Manage contacts</a>
        <script>var Office={onReady(){},context:{platform:'OfficeOnline'}};var BerliI18n={t:()=> 'Preparing'};
        window.defaultPopupBlocked=window.open('about:blank','controlProbe')===null;</script>
        <script src="taskpane.js"></script><script>sessionName='fixture-session';crmUserId='19x3';
        localStorage.setItem('berliCRM.outlook.session',JSON.stringify({username:'Fixture',sessionName:'fixture-session',userId:'19x3'}));
        document.getElementById('launch').addEventListener('click',launchContactsManager);</script>`);
      if (url.pathname === '/contacts.html') {
        let html = await fs.readFile(path.join(root, 'contacts.html'), 'utf8');
        html = html.replace(/<script src="https:\/\/appsforoffice[^>]*><\/script>/,
          '<script>var Office={onReady:()=>new Promise(()=>{}),context:{}};</script>');
        html = html.replace(/<script src="assets\/contacts-sso.bundle.js[^>]*><\/script>/,
          `<script>window.BerliContactsAuth={connectSilently:async()=>{throw {errorCode:'login_required',message:'AADSTS50058 synthetic fixture'};}};
          const originalFetch=window.fetch.bind(window);window.fetch=(url,init)=>originalFetch(
            String(url).startsWith('https://graph.microsoft.com/')?'/pane-graph':url,init);</script>`);
        return send(html);
      }
      if (url.pathname === '/contacts-auth.html') return send(`<!doctype html><p>Local synthetic Microsoft response</p>
        <script src="contacts-auth-relay.js"></script><script>(async()=>{
          const relay=BerliAuthRelay.readContext(location,sessionStorage);
          const payload=await BerliAuthRelay.encrypt(relay,{type:'berliCRM.contacts.connected',token:'synthetic-not-a-real-token',
            account:'fixture-account',username:'fixture@example.test',expiresAt:Date.now()+60000});
          await fetch('rest/outlook.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
            action:'completeContactsAuthRelay',flowId:relay.flowId,writeKey:relay.writeKey,payload})});window.close();
        })().catch(error=>document.body.textContent=error.message);</script>`);
      if (pagePath === '/rest/outlook.php') {
        let body = ''; for await (const bytes of request) body += bytes;
        const input = JSON.parse(body); let payload;
        if (input.action === 'getUserPreferences') payload = { language: 'de_de' };
        else if (input.action === 'contactTransfer') { if (input.transfer?.mode !== 'describe') contactWrites++; payload = metadata; }
        else if (input.action === 'searchContacts') payload = {
          contacts: [{ id: input.page === 2 ? '4x51' : '4x1', firstname: 'CRM', lastname: input.page === 2 ? 'Second page' : 'Fixture', email: 'crm@example.test' }],
          page: input.page, pageSize: 50, hasMore: input.page === 1, total: 51,
        };
        else if (input.action === 'getContactsMicrosoftConfig') payload = { configured: true, clientId: 'fixture', tenantId: 'fixture',
          redirectUri: crmOrigin + (url.pathname.startsWith('/outlook-addin/') ? '/outlook-addin' : '') + '/contacts-auth.html' };
        else if (input.action === 'beginContactsAuthRelay') {
          payload = { flowId: crypto.randomBytes(32).toString('hex'), writeKey: crypto.randomBytes(32).toString('hex'), expiresAt: Date.now() + 300000 };
          flows.set(payload.flowId, { ...payload });
        } else if (input.action === 'completeContactsAuthRelay') {
          assert.equal(flows.get(input.flowId).writeKey, input.writeKey);
          flows.get(input.flowId).payload = input.payload; completed = true; payload = { accepted: true };
        } else if (input.action === 'pollContactsAuthRelay') {
          const flow = flows.get(input.flowId); payload = flow?.payload ? { state: 'ready', payload: flow.payload } : { state: 'pending', payload: null };
        } else if (input.action === 'closeContactsAuthRelay') { flows.delete(input.flowId); payload = { closed: true }; }
        else throw new Error('Unexpected fixture API: ' + input.action);
        return send(JSON.stringify({ success: true, payload }), 'application/json');
      }
      const name = pagePath.slice(1);
      if (!['taskpane.js', 'contacts.js', 'contacts.css', 'language.js', 'contact-model.js', 'contact-graph.js', 'contacts-auth-relay.js',
        'settings-connection.js', 'contacts-auth.css', 'contacts-layout.js'].includes(name)) {
        response.writeHead(404); return response.end();
      }
      return send(await fs.readFile(path.join(root, name), 'utf8'), name.endsWith('.css') ? 'text/css' : 'application/javascript');
    } catch (error) { response.writeHead(500); response.end(error.message); }
  });
  try {
    await fs.writeFile(path.join(profile, 'user.js'), [
      ['dom.disable_open_during_load', true], ['browser.shell.checkDefaultBrowser', false],
      ['browser.startup.page', 0], ['browser.sessionstore.resume_from_crash', false],
      ['browser.aboutwelcome.enabled', false], ['browser.startup.homepage_override.mstone', 'ignore'],
      ['startup.homepage_welcome_url', ''],
    ].map(([name, value]) => 'user_pref(' + JSON.stringify(name) + ',' + JSON.stringify(value) + ');').join('\n'));
    server.listen(port, '127.0.0.1'); await once(server, 'listening');
    geckodriver = spawn(process.env.BERLI_GECKODRIVER || 'geckodriver', ['--host', '127.0.0.1', '--port', String(controlPort)],
      { windowsHide: true, stdio: 'ignore' });
    for (let attempt = 0; attempt < 60; attempt++) {
      try { await driver('GET', '/status'); break; } catch { await delay(100); }
    }
    const session = await driver('POST', '/session', { capabilities: { alwaysMatch: {
      browserName: 'firefox', 'moz:firefoxOptions': { binary, args: ['-headless', '-profile', profile],
        prefs: { 'dom.disable_open_during_load': true } },
    } } });
    sessionId = session.sessionId;
    const browserVersion = session.capabilities.browserVersion;
    const sessionRoute = '/session/' + sessionId;
    await driver('POST', sessionRoute + '/window/rect', { width: 450, height: 1100 });
    await driver('POST', sessionRoute + '/url', { url: 'http://127.0.0.1:' + port + '/outlook' });
    const frame = await driver('POST', sessionRoute + '/element', { using: 'css selector', value: '#crm' });
    await driver('POST', sessionRoute + '/frame', { id: frame });
    const evaluate = script => driver('POST', sessionRoute + '/execute/sync', { script, args: [] });
    const screenshot = async name => {
      // Firefox cannot capture from inside a cross-origin frame; capture its top-level window instead.
      await driver('POST', sessionRoute + '/frame', { id: null });
      const bytes = await driver('GET', sessionRoute + '/screenshot');
      await fs.writeFile(path.join(root, 'build/' + name), Buffer.from(bytes, 'base64'));
      const currentFrame = await driver('POST', sessionRoute + '/element', { using: 'css selector', value: '#crm' });
      await driver('POST', sessionRoute + '/frame', { id: currentFrame });
    };
    const control = await evaluate('return window.defaultPopupBlocked;');
    assert.equal(control, true, 'Default popup blocking must remain enabled in this isolated profile');
    const anchor = await driver('POST', sessionRoute + '/element', { using: 'css selector', value: '#launch' });
    await driver('POST', sessionRoute + '/element/' + anchor['element-6066-11e4-a52e-4f735466cecf'] + '/click', {});
    let status;
    for (let attempt = 0; attempt < 80; attempt++) {
      const value = await evaluate("return document.getElementById('contactMicrosoftStatus')?.textContent || location.href;");
      status = value;
      if (status?.includes('fixture@example.test')) break;
      await delay(100);
    }
    assert.equal(completed, true, 'The prepared popup must complete the encrypted relay');
    assert.match(status, /fixture@example\.test/);
    const windows = await driver('GET', sessionRoute + '/window/handles');
    assert.equal(windows.length, 1, 'No unused authentication window remains');
    process.stdout.write('Firefox ' + browserVersion + ': popup blocking enabled; trusted contacts click, delayed Office readiness, encrypted popup handover and connection passed. Synthetic responses; no Microsoft account used.\n');
    for (let attempt = 0; attempt < 80; attempt++) {
      if ((await evaluate("return document.getElementById('contactOutlookResults')?.textContent || '';"))?.includes('Outlook Fixture')) break;
      await delay(100);
    }
    assert.equal(contactReads, 1, 'Opening contacts automatically reads exactly one page');
    assert.match(await evaluate("return document.getElementById('contactOutlookResults').textContent;"), /Outlook Fixture/);
    assert.equal(await evaluate("return !document.getElementById('contactOutlookSection').hidden && document.getElementById('contactCrmSection').hidden && document.documentElement.scrollWidth<=innerWidth;"), true);
    await evaluate("const checkbox=document.querySelector('#contactOutlookResults input');checkbox.checked=true;checkbox.dispatchEvent(new Event('change'));");
    await screenshot('contacts-taskpane-preview-outlook.png');
    const crmTab = await driver('POST', sessionRoute + '/element', { using: 'css selector', value: '#contactShowCrm' });
    await driver('POST', sessionRoute + '/element/' + crmTab['element-6066-11e4-a52e-4f735466cecf'] + '/click', {});
    await evaluate("document.getElementById('contactSearch').value='Fixture';document.getElementById('contactSearchForm').requestSubmit();");
    for (let attempt = 0; attempt < 40; attempt++) {
      if ((await evaluate("return document.getElementById('contactCrmResults').textContent;"))?.includes('CRM Fixture')) break;
      await delay(100);
    }
    assert.match(await evaluate("return document.getElementById('contactCrmResults').textContent;"), /CRM Fixture/);
    const nextPage = await driver('POST', sessionRoute + '/element', { using: 'css selector', value: '#contactCrmNext' });
    await driver('POST', sessionRoute + '/element/' + nextPage['element-6066-11e4-a52e-4f735466cecf'] + '/click', {});
    for (let attempt = 0; attempt < 40; attempt++) {
      if ((await evaluate("return document.getElementById('contactCrmPageStatus').textContent;"))?.includes('Seite 2')) break;
      await delay(100);
    }
    assert.match(await evaluate("return document.getElementById('contactCrmResults').textContent;"), /Second page/);
    assert.equal(await evaluate("return document.getElementById('contactCrmPageStatus').textContent;"), 'Seite 2 · Treffer 51–51 von 51');
    assert.equal(await evaluate("return document.getElementById('contactCrmNext').disabled;"), true);
    const previousPage = await driver('POST', sessionRoute + '/element', { using: 'css selector', value: '#contactCrmPrevious' });
    await driver('POST', sessionRoute + '/element/' + previousPage['element-6066-11e4-a52e-4f735466cecf'] + '/click', {});
    for (let attempt = 0; attempt < 40; attempt++) {
      if ((await evaluate("return document.getElementById('contactCrmPageStatus').textContent;"))?.includes('Seite 1')) break;
      await delay(100);
    }
    assert.match(await evaluate("return document.getElementById('contactCrmResults').textContent;"), /CRM Fixture/);
    assert.equal(await evaluate("return document.querySelector('#contactOutlookResults input').checked && document.getElementById('contactOutlookSection').hidden;"), true);
    await screenshot('contacts-taskpane-preview-crm.png');
    await driver('POST', sessionRoute + '/window/rect', { width: 1280, height: 1050 });
    await delay(150);
    assert.equal(await evaluate("const a=document.getElementById('contactOutlookSection').getBoundingClientRect(),b=document.getElementById('contactCrmSection').getBoundingClientRect();return a.width>0 && a.right<=b.left && document.querySelector('#contactOutlookResults input').checked;"), true);
    assert.equal(contactReads, 1); assert.equal(contactWrites, 0);
    process.stdout.write('Firefox existing task pane: automatic contact read, narrow tabs, retained selection, CRM search, next/previous pages, wide columns and no writes passed with synthetic responses. No extra app or Teams SDK.\n');
    await driver('POST', sessionRoute + '/url', { url: crmOrigin + '/settings' });
    const check = await driver('POST', sessionRoute + '/element', { using: 'css selector', value: '#outlookCheckConnection' });
    await driver('POST', sessionRoute + '/element/' + check['element-6066-11e4-a52e-4f735466cecf'] + '/click', {});
    let settingsStatus;
    for (let attempt = 0; attempt < 100; attempt++) {
      settingsStatus = await evaluate("return document.getElementById('outlookConnectionStatus').textContent;");
      if (settingsStatus === 'Read access succeeded') break;
      await delay(100);
    }
    assert.equal(settingsHadNoOpener, true, 'COOP must actually sever window.opener during this test');
    assert.equal(settingsGraphReads, 1, 'The connection check only reads once');
    assert.equal(settingsStatus, 'Read access succeeded');
    assert.equal((await driver('GET', sessionRoute + '/window/handles')).length, 1);
    process.stdout.write('Firefox CRM settings: cross-origin COOP redirect, missing opener, acknowledged channel reply and read-only Graph check passed with synthetic responses.\n');
  } finally {
    if (sessionId) try { await driver('DELETE', '/session/' + sessionId); } catch {}
    if (geckodriver && geckodriver.exitCode === null) { geckodriver.kill(); await Promise.race([once(geckodriver, 'exit'), delay(3000)]); }
    server.closeAllConnections(); await new Promise(resolve => server.close(resolve));
    // Deliberately keep the fresh, isolated profile for a failure audit; never touch the user's profile.
    process.stdout.write('Isolated test profile: ' + profile + '\n');
  }
}
main().catch(error => { process.stderr.write(error.stack + '\n'); process.exitCode = 1; });
