# Automated Vulnerability Discovery and Remediation Pipeline

## 1. Environment Setup

- The project uses Docker and Docker Compose to run WordPress with MySQL.
- The WordPress image is built from `wordpress/Dockerfile`.
- The current Dockerfile is a hardened image based on:
  - `wordpress:latest`
- The database image is `mysql:5.7`.
- The local site runs on `http://localhost:8080`.
- The GitHub Actions workflow is stored in `.github/workflows/scan.yml`.
- The workflow can run on push to `main` or manually from the Actions tab.

## 2. Findings Overview

The first goal was to create an initial scan before applying fixes.

WPScan checks for:

- WordPress version
- exposed headers
- `readme.html`
- users
- plugins
- themes
- known vulnerabilities when the WPScan API token is available

Initial token-enabled scan:

- Initial scan completed
  - Severity: Info
  - Evidence: WPScan output from May 20, 2026 at 20:42-20:49
  - Result: WPScan used the API token successfully.
  - Result: WPScan exit code was `5` because vulnerabilities were found.

- WordPress headers exposed
  - Severity: Low
  - Evidence: `Server: Apache/2.4.67 (Debian)` and `X-Powered-By: PHP/8.3.31`
  - Impact: Attackers can identify server and PHP versions for fingerprinting.

- XML-RPC enabled
  - Severity: Medium
  - Evidence: `http://localhost:8080/xmlrpc.php`
  - Impact: Attackers may try XML-RPC login brute force, pingback abuse, or denial-of-service techniques.

- WordPress readme exposed
  - Severity: Low
  - Evidence: `http://localhost:8080/readme.html`
  - Impact: Attackers can use it for WordPress fingerprinting.

- External WP-Cron enabled
  - Severity: Low
  - Evidence: `http://localhost:8080/wp-cron.php`
  - Impact: Attackers may abuse repeated requests to create extra load.

- WordPress version detected
  - Severity: Info
  - Evidence: WordPress `6.9.4`
  - Impact: The version is currently listed as latest, but exposing the exact version helps attackers choose tests.

- Default theme detected
  - Severity: Info
  - Evidence: `twentytwentyfive` version `1.4`
  - Impact: The theme is up to date, but theme fingerprinting is possible.

- Akismet plugin detected with vulnerability
  - Severity: High
  - Evidence: WPScan detected `akismet` and reported one vulnerability.
  - Vulnerability: Akismet `2.5.0-3.1.4` unauthenticated stored XSS.
  - Fixed version: `3.1.5`
  - CVE: `CVE-2015-9357`
  - Impact: An attacker could exploit the vulnerable plugin range for stored cross-site scripting if an affected version is present. WPScan could not determine the installed version, so the safe remediation was to remove the unused plugin.

- No vulnerable themes found
  - Severity: Info
  - Evidence: WPScan reported `No themes Found.`
  - Impact: No vulnerable themes were detected in the initial scan.

- User enumeration possible
  - Severity: Medium
  - Evidence: WPScan identified user `dutzz23`.
  - Impact: Attackers can use known usernames for password guessing or brute-force attempts.

- WPScan API token worked
  - Severity: Info
  - Evidence: WPScan reported `WPScan DB API OK`.
  - Impact: Vulnerability database results were available during the scan.

Remediated local scan result:

- Scan completed successfully
  - Evidence: `scans/remediated/wpscan-remediated.txt`
  - Result: WPScan exit code `0`

- Server headers improved
  - Evidence: `Server: Apache`
  - Result: The detailed Debian/Apache version is no longer exposed.

- PHP header removed
  - Evidence: `X-Powered-By` was not reported in the remediated scan.
  - Result: PHP version disclosure was reduced.

- XML-RPC no longer reported
  - Evidence: The remediated scan did not report `xmlrpc.php`.
  - Result: XML-RPC brute-force and pingback abuse risk was reduced.

- WordPress readme no longer reported
  - Evidence: The remediated scan did not report `readme.html`.
  - Result: WordPress fingerprinting was reduced.

- WP-Cron no longer reported
  - Evidence: The remediated scan did not report `wp-cron.php`.
  - Result: External WP-Cron abuse risk was reduced.

- WordPress version not detected
  - Evidence: WPScan reported `The WordPress version could not be detected.`
  - Result: Exact WordPress version fingerprinting was reduced.

- No users found
  - Evidence: WPScan reported `No Users Found.`
  - Result: User enumeration was reduced.

- No plugins found
  - Evidence: WPScan reported `No plugins Found.`
  - Result: No vulnerable plugins were detected in the local remediated scan.

- No vulnerable themes found
  - Evidence: WPScan reported `No themes Found.`
  - Result: No vulnerable themes were detected in the local remediated scan.

- Remaining theme fingerprinting
  - Evidence: WPScan still detected `twentytwentyfive` version `1.4`.
  - Result: This remains visible because the active theme stylesheet is public.

- Must-use plugin path detected
  - Evidence: WPScan detected `wp-content/mu-plugins/`.
  - Result: This is expected because a must-use hardening plugin is installed.

- Akismet removed and scan repeated
  - Evidence: The updated remediated scan reports `No plugins Found.`
  - Result: The Akismet finding was removed from the local remediated scan.

## 3. Remediation Steps

The remediation step was based on the initial token-enabled WPScan result. That scan showed XML-RPC enabled, `readme.html` exposed, external WP-Cron enabled, visible server/PHP headers, WordPress version fingerprinting, theme fingerprinting, Akismet plugin exposure, an Akismet XSS finding, and user enumeration.

Fixes added:

- Blocked `xmlrpc.php` through Apache.
- Blocked direct access to `wp-cron.php` through Apache.
- Removed `readme.html`, `license.txt`, and theme `readme.txt` files from the image.
- Removed the default Akismet plugin from the image.
- Removed Akismet from existing WordPress runtime volumes during the workflow.
- Added Apache hardening in `wordpress/apache-hardening.conf`.
- Added PHP hardening in `wordpress/php-hardening.ini`.
- Disabled PHP version exposure.
- Added a WordPress must-use plugin in `wordpress/wp-hardening.php`.
- Removed the WordPress generator tag.
- Made login errors generic.
- Restricted unauthenticated REST API user enumeration.
- Blocked author archive enumeration.
- Changed generated `robots.txt` output.
- Disabled WordPress dashboard file editing.
- Disabled built-in WP-Cron execution.
- Removed unnecessary tools from the Docker image.
- Changed Apache to listen on port `8080` so the container can run as `www-data`.

Scan evidence:

- The initial scan is kept under `scans/initial/`.
- The new remediated scan will be written under `scans/remediated/`.
- The workflow uploads the new scan as `wpscan-remediated-results`.
- The new scan output file is `scans/remediated/wpscan-remediated.txt`.

## 4. Fixed Image Build

- Fixed image build files were added.
- GHCR image not pushed yet.
- Docker Hub image not pushed yet.
- Registry push will be done after the remediated scan is checked.

## 5. Tooling Justification

- Docker is used to run WordPress and MySQL in containers.
- Docker Compose is used so the local setup and GitHub Actions setup are similar.
- WPScan is used to scan WordPress for common security issues.
- GitHub Actions is used to run the scan automatically.
- A WPScan API token was added as a GitHub secret named `WPSCAN_API_TOKEN`.

## 6. DevSecOps Strategy

- The first scan runs before fixes are applied.
- The next scan runs after the image is patched and hardened.
- The workflow writes the new scan to a different folder so the initial scan is not overwritten.
- This gives before/after evidence for the report.
