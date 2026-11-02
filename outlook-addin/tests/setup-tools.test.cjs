const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const php = process.env.PHP_BINARY || (process.platform === 'win32' ? 'C:\\php\\php.exe' : 'php');
const addin = path.join(__dirname, '..');

function fixture(t) {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'berlicrm-setup-test-'));
  const files = [], directories = new Set([root]);
  function mkdir(directory) {
    if (directories.has(directory)) return;
    mkdir(path.dirname(directory));
    fs.mkdirSync(directory); directories.add(directory);
  }
  function write(relative, content) {
    const target = path.join(root, relative);
    mkdir(path.dirname(target)); fs.writeFileSync(target, content); files.push(target);
  }
  for (const name of ['generateOutlookManifest.php', 'registerOutlookAddinOperation.php', 'debugOutlookWebservice.php', 'checkVerteilerWebservice.php']) {
    write('outlook-addin/build/' + name, fs.readFileSync(path.join(addin, 'build', name)));
  }
  write('outlook-addin/update.php', fs.readFileSync(path.join(addin, 'update.php')));
  mkdir(path.join(root, 'unrelated'));
  t.after(() => {
    for (const file of files) fs.unlinkSync(file);
    for (const directory of [...directories].sort((a, b) => b.length - a.length)) fs.rmdirSync(directory);
  });
  function run(name, args = []) {
    const result = spawnSync(php, [path.join(root, 'outlook-addin/build', name), ...args], { cwd: path.join(root, 'unrelated'), encoding: 'utf8', windowsHide: true, timeout: 10000 });
    if (result.error) throw result.error;
    return result;
  }
  return { root, write, run };
}

test('relocated manifest generator finds CRM configuration from an unrelated working directory', t => {
  const env = fixture(t);
  env.write('config.inc.php', "<?php $site_URL = 'https://crm.example.test/installation';");
  env.write('outlook-addin/manifest.xml', fs.readFileSync(path.join(addin, 'manifest.xml')));
  env.write('outlook-addin/manifest.generated.xml', '');
  const result = env.run('generateOutlookManifest.php');
  assert.equal(result.status, 0, result.stderr);
  const generated = fs.readFileSync(path.join(env.root, 'outlook-addin/manifest.generated.xml'), 'utf8');
  assert.ok(generated.includes('https://crm.example.test/installation/outlook-addin/taskpane.html'));
  assert.ok(!generated.includes('https://crm.example.org'));
});

test('relocated CLI installer bootstraps CRM and calls the shared initializer from another directory', t => {
  const env = fixture(t);
  env.write('config.inc.php', '<?php');
  for (const file of ['includes/Loader.php', 'include/utils/utils.php', 'include/database/PearDatabase.php', 'includes/runtime/BaseModel.php']) env.write(file, '<?php');
  env.write('modules/Settings/OutlookAddin/models/Module.php', `<?php
    class Settings_OutlookAddin_Module_Model {
      public static function registerSettingsMenu() { echo "Settings menu initialized.\\n"; }
      public static function registerOperation() {
        if (!is_readable('config.inc.php')) throw new RuntimeException('Wrong CRM directory');
        echo "Shared initializer called.\\n";
      }
    }`);
  const result = env.run('registerOutlookAddinOperation.php');
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /Shared initializer called/);
  assert.match(result.stdout, /web services registered/);
});

test('missing CRM configuration fails before relocated setup tools bootstrap anything', t => {
  const env = fixture(t);
  for (const [name, args] of [['generateOutlookManifest.php', []], ['registerOutlookAddinOperation.php', []], ['checkVerteilerWebservice.php', ['--user-id', '3']]]) {
    const result = env.run(name, args);
    assert.equal(result.status, 1, name);
    assert.match(result.stderr, /config.inc.php|outlook-addin\/build/);
    assert.doesNotMatch(result.stderr, /Failed opening required|Fatal error/);
  }
});

test('relocated diagnostics advertise the new paths without needing CRM access', t => {
  const env = fixture(t);
  for (const name of ['debugOutlookWebservice.php', 'checkVerteilerWebservice.php']) {
    const result = env.run(name, ['--help']);
    assert.equal(result.status, 0, result.stderr);
    assert.ok(result.stdout.includes('outlook-addin/build/' + name));
  }
});

test('HTTP debugger loads configuration adjacent to the add-in instead of the launch directory', t => {
  const env = fixture(t);
  // An invalid URL distinguishes a loaded file from the missing-configuration branch;
  // validation fails before any network request or credential prompt.
  env.write('outlook-addin/rest/outlook.config.php', "<?php return ['crm_webservice_url' => 'http://invalid.example.test/webservice.php'];");
  const result = env.run('debugOutlookWebservice.php');
  assert.equal(result.status, 1);
  assert.match(result.stderr, /HTTPS-Webservice-URL/);
  assert.doesNotMatch(result.stderr, /Bitte --url angeben/);
});
