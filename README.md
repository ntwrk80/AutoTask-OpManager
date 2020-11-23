## Note: New version is a work in progress (fresh start with more customization)
# AutoTask-OpManager
This is a fork of the code from ATSlack-Notify to allow an interface between Autotask PSA and ManageEngine's OpManager software. I am not a developer by trade so YMMV.


Need to change stuff below here. :)
####Usage
* ticketSlack.php & ticketSlack2.php: ticketSlack.php is legacy. ticketSlack2.php is used by AT Extensions to trigger an alert to a specefic room when a new ticket is opened.
* ticketReply.php: COMING SOON. Will alert the ticket owner via Slack when the customer places a reply in the ticket.

## Installation

Installation is simple. Download as a ZIP and extract on your web server. Set permissions for config.php to 0600. Edit config.php and fill in the appropriate variables. Your "Autotask Realm" is the first part of the URL after you login (examples: ww5, ww14). Your webservices URL is based on the Realm. If you're in ww5, than your services host should be webservices5.

Your Slack URL is the URL of your Slack Webhook (YOUR_TEAM.slack.com/apps/A0F7XDUAZ-incoming-webhooks). Create your webhook there. You will also want to set the name and icon as we do not do that in ATSlack-Notify. You also need to set the channel within config.php that you want the messages posted to. It does not matter what you picked on Slack.

Username and password should be API credentials for your Autotask instance. You can hit up your account rep to get these for free.

$extensiontoken is ***IMPORTANT:*** This adds a layer of security to the system, preventing a random person from war dialing ticket numbers :). Set this to a RANDOM value (best not to use special characters). Then add it to your Ticket Extension per the below instructions!

## MySQL Database

You will need to manually create a MySql database as well as a user with full access. Save that information in the database section of config.php.
$dbusername = MySQL User
$dbpassword = MySQL User Pass
$dbname = Database Name
$dbhost = MySQL Server (usually localhost)

NOW YOU WILL SETUP EACH FUNCTION OF THE SOFTWARE

### New Ticket Alerts (ticketSlack2.php)

This functions posts a message to a room of your choice when a new ticket is created.

First login to Autotask and go to Admin>Extensions and Integrations>Other Extensions and Tools>Extension Callouts

Create  **NEW** extension callout with the following variables:


* Memorable Name
* URL: https://yourserver/folder-where-atslack-is/ticketSlack2.php?s=YOURSECURITYTOKEN
* Leave Username, Password, and UDF blank
* Transport Method POST
* Data Format Name Value Pair

Save & Close

Now create a workflow rule to fire this callout. For my purposes, I set my workflow to fire when a new ticket is created by an external contact, and filtered it to certain queues that matter most to me. You can design yours however you want. Just make sure that you select your callout under actions.

### Ticket Reply Direct Messages (ticketReply.php)

This function will send a direct message to the ticket owner in Slack when a reply is added by the contact. If the user has not been mapped or the ticket is unassigned, it will send it to the same room as new ticket messages.

#### Autotask Steps

Create a ticket extension as before with the following paramters

* Memorable Name
* URL: https://yourserver/folder-where-atslack-is/ticketReply.php?s=YOURSECURITYTOKEN
* Leave Username, Password, and UDF blank
* Transport Method POST
* Data Format Name Value Pair

Save & Close

Now create a workflow rule to fire this callout. For my purposes, I set my workflow conditions to modified by ticket contact.

#### Setup Database
Earlier, you created a MySQL Database and saved the info. Now you need to add the tables. To do so go to https://server/atslack/installdb.php and follow the instructions. This script will format the tables in the database.

***DELETE INSTALLDB.PHP ONCE YOU ARE DONE RUNNING IT***

#### Setup Slack SLASH command.
* Login to https://your_team.slack.com/apps/A0F82E8CA-slash-commands and click Add Configuration
* For command, enter the command you want to use, starting with a slash (instructions refer to /usermap so we recommend you use those)
* For URL enter https://server/atslackfolder/usermapping.php
* For Method, Select GET
* Token is chosen by Slack, copy that value and set it as $dbmantoken in config.php (THIS IS IMPORTANT)
* Set whatever name you would like, I use AT User Mapping
* Set an icon if you want to be all fancy
* Set autocomplete and help text if you wish
* click SAVE INTEGRATION

Here is an example of right: https://i.imgur.com/fgQKz4j.png

#### Map Your Users

**Autotask Username (atusername):** Your autotask username if the username that you use to login to autotask WITHOUT @domain.com. So, if you type john@domain.com when you login to Autotask, your Autotask Username is john

**Slack Useraname (slackname):** Your slack username.

**SYNTAX:**

* Add User: /usermap addmap [slackname] [atusername]
* List Mappings: /usermap listmap
* Remove a Mapping /usermap removemap [slackname]

NOTE: Only authorized Slack users get to use /usermap. This is defined in config.php under $adminlist. Use Slack usernames in $adminlist.

**Help:** Run the command /usermap help for the syntax of mapping and unmapping users


## That's it, you should be good to go :). ENJOY!!

##Testing
You can test the integration easily. The included test.php file contains a form that will input the proper variables.

To test, navigate to https://server/atslack/test.php?s=YOURSECURITYTOKEN (don't forget the token)

For Ticket ID enter the ticket ID. This can be found by clicking on the ticket in Autotask. Check the url after ?ticketID=
For ticket number, enter the correct ticket number (T20170101.0001 for example).

If you want to view the output in the browser instead of pushing it to Slack, open config.php and set $testmode to true.

## Security

NOTE: Unless you modify the code, SSL is required for this script to function.

## Thanks!

I borrowed code from the CWSlack project for this project.
