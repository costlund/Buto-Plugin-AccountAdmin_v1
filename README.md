# Buto-Plugin-AccountAdmin_v1

<p>Handle account tables in db.</p>

<a name="key_0"></a>

## Settings

<p>This example is for url /users/desktop.</p>
<pre><code>users:
  plugin: 'account/admin_v1'
  settings:
    mysql: 'yml:/../buto_data/theme/[theme]/mysql.yml'</code></pre>
<p>Url.</p>
<pre><code>/users/desktop</code></pre>

<a name="key_1"></a>

## Usage



<a name="key_2"></a>

## Pages



<a name="key_2_0"></a>

### page_account_base



<a name="key_2_1"></a>

### page_account_base_capture



<a name="key_2_2"></a>

### page_account_base_form



<a name="key_2_3"></a>

### page_account_delete



<a name="key_2_4"></a>

### page_account_delete_yes



<a name="key_2_5"></a>

### page_account_log



<a name="key_2_6"></a>

### page_account_log_data



<a name="key_2_7"></a>

### page_account_role_capture



<a name="key_2_8"></a>

### page_account_role_delete



<a name="key_2_9"></a>

### page_account_role_form



<a name="key_2_10"></a>

### page_account_roles



<a name="key_2_11"></a>

### page_account_session_content



<a name="key_2_12"></a>

### page_account_session_delete



<a name="key_2_13"></a>

### page_account_view



<a name="key_2_14"></a>

### page_accounts



<a name="key_2_15"></a>

### page_accounts_data



<a name="key_2_16"></a>

### page_desktop



<a name="key_2_17"></a>

### page_stat_browser



<a name="key_2_18"></a>

### page_stat_os



<a name="key_2_19"></a>

### page_stat_signin



<a name="key_3"></a>

## Widgets



<a name="key_4"></a>

## Event



<a name="key_5"></a>

## Construct



<a name="key_5_0"></a>

### __construct



<a name="key_6"></a>

## Methods



<a name="key_6_0"></a>

### init



<a name="key_6_1"></a>

### get_accounts



<a name="key_6_2"></a>

### get_browser_detection



<a name="key_6_3"></a>

### get_element_stat



<a name="key_6_4"></a>

### getAccount



<a name="key_6_5"></a>

### getSessionFilename



<a name="key_6_6"></a>

### getSessionFileExist



<a name="key_6_7"></a>

### getSessionFileTime



<a name="key_6_8"></a>

### frm_account_base_form_render



<a name="key_6_9"></a>

### frm_account_role_form_render



<a name="key_6_10"></a>

### db_role_select_one



<a name="key_6_11"></a>

### frm_account_base_form_capture



<a name="key_6_12"></a>

### frm_account_role_form_capture



<a name="key_6_13"></a>

### createAccount



<a name="key_6_14"></a>

### getAccountLog



<a name="key_6_15"></a>

### getYml



<a name="key_6_16"></a>

### runSQL



<a name="key_6_17"></a>

### executeSQL



<a name="key_6_18"></a>

### validate_username_or_email



<a name="key_6_19"></a>

### validate_role



<a name="key_6_20"></a>

### db_account_username_exist



<a name="key_6_21"></a>

### db_account_email_exist



