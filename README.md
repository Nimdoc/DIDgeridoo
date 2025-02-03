# DIDgeridoo

This is a WordPress plugin that lets you manage your DID for your website and optionally lets users add a handle and DID in their profiles for Bluesky or other apps that use ATProto handles and DIDs.

## How to do it

This guide appies mainly to Bluesky, but this process may also work for other apps that are built on ATProto in the future.

### Main Domain DID

1. Make sure that your permalink structure is set to something other than "plain" in Settings > Permalinks, otherwise this plugin WILL NOT WORK.

2. Install this plugin either through WordPress plugins or by manually uploading a release to your WordPress site, then activate it.

3. Open Bluesky, then in the Settings menu, click on Account > Handle > I have my own domain > No DNS Panel

4. Type in the domain of your WordPress site, as it appears in the WordPress General Settings under Site Address, but without the protocol (Without `http://` or `https://`)

5. Copy the DID at the bottom. It will look something like `did:plc:test:example`

6. Go back to your WordPress admin section, go to Settings > DIDgeridoo, and paste your DID in the field under "Main DID", then click "Save"

7. Then go back to Bluesky and click "Verify Text File", and then click "Update to [Your new user handle]"

Then your Bluesky account should be using the domain from your WordPress site as your handle.

### Organization Mode (Advanced)

If you want to have multiple account handles as subdomains on your WordPress site, you MUST have a wildcard A record in your DNS settings that points to your WordPress site.

For example:
```
*.example.com
```

Once you have this A record, you can go back to the WordPress Admin section, then back to Settings > DIDgeridoo, and check the "Enable Organization Mode" checkbox.

By default, the user label will just be prepended to the WordPress Site Address. For example, if the A record would be `*.example.com`, then handles would look like:

```
tom.example.com
```

If you would like additional labels between user handle label and the top level domain, you may specify them in the field under Subdomain. For example

```
tom.bsky.example.com
```

Or

```
cool-user.bsky.users.example.com
```

You can then click the "Test Subdomain" button to check that your WordPress site is reachable with your Subdomain settings.

Users can then specify what handle they want and their DID in the WordPress admin section in their profiles under "ATProto DID Settings."

## Development

Here are some notes if you want to work on this plugin yourself, and for myself when I forget them.

### Install packages and build

Install
```bash
npm install
```

Build for release
```bash
npm build
```

Development
```bash
npm run start
```

### Internationalization

```bash
wp i18n make-pot src languages/didgeridoo.pot --domain=didgeridoo
```