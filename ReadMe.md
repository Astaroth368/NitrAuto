# NitrAuto

NitrAuto is a basic task scheduler designed for Nitrado hosted ARK Survival Ascended servers. At the time of writing this, Nitrado's dashboard is able to schedule server restarts, but it only gives online players a 5 second warning and has no option to force save the game or destroy wild dinos, resulting in short roll backs and frustraited players.

# Features
- Scheduled server restarts (Nitrado servers only)
- Scheduled warning messages sent via in-game chat **(Broadcasts are supported but won't work until WildCard fix a bug)**
- Scheduled 'Destroy Wild Dinos'
- Scheduled world/game saves (E.g. just before a restart - not intended to replace the built-in auto save feature)
- Schedule other commands which work with RCON
- Schedule server rate changes using ARK's `CustomDynamicConfigUrl` setting

> [!NOTE]
> There is currently an issue with RCON connectivity in ARK Survival Ascended which could impact the reliability of this tool. Unfortunately we need to wait for WildCard to resolve this issue, but as this tool only makes a brief connection, I haven't seen any issues yet on my server.

# Requirements
- Web Server (paid or free)
- PHP 8
- MySQL Database
- Access to cron jobs (or Task Scheduler for Windows web servers)
    - Alternatively, third party services such as [cron-job.org](https://cron-job.org) can be used.

# Installation
1. Log into your Nitrado account and go to [https://server.nitrado.net/eng/developer/tokens](https://server.nitrado.net/eng/developer/tokens)
2. Create a long life token with `rootserver` and `service` permissions
3. Copy the token and keep it safe as you won't be able to view it again without creating a new one
4. Log into your phpMyAdmin and if you haven't already, create a database
5. Click your new database on the left hand side, then click Import at the top
6. Click Choose File and browse your computer for the `SQLExport.sql` file
7. Set the character set to `utf-8`
8. Click Import at the bottom of the page
9. Edit `NitrAutoConfig.php` and enter your:
    - MySQL host (Just use "localhost" if MySQL is installed on the same server as your web server)
    - MySQL username
    - MySQL password
    - MySQL database name
    - Nitrado Long Life Token
    - RCON password (This tool currently only supports one password for all servers)
    - Dynamic config file path E.g. "dynamicconfig.ini"
10. Upload the source files to your web server's root folder (You can use sub folders but will need to adjust the include path for the config file)
11. `NitrAutoConfig.php` should be placed outside of your web root if possible. It's default include path is one directory above the web root (I.e. "../NitrAutoConfig.php")
12. Configure a cron job (or Scheduled Task on Windows servers) to run the `nitrauto.cron.php` file every minute. How you do this will depend on your web host so unfortunately I can't provide instructions but there is lots of information on the Internet. The time string needed for the cron job to run every minute is `* * * * *`. Alternatively, if your server doesn't allow 1 minute intervals, you can use a third party server such as [cron-job.org](https://cron-job.org)

# Scheduled Server Rates Configuration
1. Create a dynamic config file with the name and path you configured in `NitrAutoConfig.php`
2. Enter the variables/settings you want to dynamically configure in that file with your default settings E.g:

> XPMultiplier=1.5
> TamingSpeedMultiplier=5.0
> HarvestAmountMultiplier=3.0
> EggHatchSpeedMultiplier=16
> BabyMatureSpeedMultiplier=10
> MatingIntervalMultiplier=0.05
> SupplyCrateLootQualityMultiplier=1.0

3. Upload the file to your web server in the location you configured in `NitrAutoConfig.php`
4. Edit your ARK server's `GameUserSettings.ini` file(s) and add the following under the `[ServerSettings]` heading. You will need to replace the link with a link to the dynamic config file you configured in `NitrAutoConfig.php`

> UseDynamicConfig=true
> CustomDynamicConfigUrl="http://YourWebServer.com/dynamicconfig.ini"

# Configuration/Creating Schedules
> [!NOTE]
> At the moment there is no UI for configuration. You will need to edit the database via phpMyAdmin or your chosen tool
1. Open phpMyAdmin and select your database
2. Check the `servers` table contains the relevant information for your ASA servers. This should be fairly self explanatory.
3. Optionally set up some dynamic server rates in the `dynamicconfigs` table
    a. Insert a record which contains your default rates in the `Settings` coulmn (just copy the content of the `dynamicconfig.ini` file you created in the steps above)
    b. Insert one or more additional records and modify the rates ready for your events/weekend rates etc.
4. Set up one or more scheduled tasks in the `scheduledtasks` table
    a. The columns are as follows:
        **ID** = Auto increments, you can ignore this column
        **Created** = Filled automatically, you can ignore this column
        **ServerID** = This needs to be the `ID` of a server in the `servers` table. This is the server this schedule will run on.
        **Hour** = Hour of the day that the task will run (0 to 23) E.g. 4PM will be 16
        **Minute** = Minute of the hour that the task will run E.g. If Hour = 16 and Minute = 30, the task will run at 4:30 PM
        **DaysOfWeek** = This determins which days of the week the task will run. This field contains a bitmask which should be entered as below:
            0 Sun Sat Fri Thu Wed Tue Mon
            0  1   1   1   1   1   1   1  = Task runs every day (Entered as: 01111111)
            0  0   0   1   0   1   0   1  = Task runs Monday, Wednesday and Friday (Entered as: 00010101)
        **SwitchToConfigID** = Setting this field to an `ID` from the `dynamicconfigs` table will change the content of your `dynamicconfig.ini` file and force your ARK server to update its config. Set to 0 if you don't want to make any changes to the server config
        **DinoWipe** = Set to 1 to destroy wild dinos, or 0 to do nothing
        **SaveWorld** = Set to 1 to force a world save, or 0 to do nothing
        **RestartServer** = Set to 1 to restart the ARK server (Nitrado only - required a Long Life Token to be configured in the `NitrAutoConfig.php` file), or 0 to do nothing
        **AltCommand** = Enter a custom command to run on the server (don't put 'cheat' or 'admincheat' at the beginning). The command must be compatible with RCON. Leave blank to do nothing.
        **MessageType** = NONE to do nothing
                        SERVERCHAT to send the content of the `Message` field using the ServerChat console command
                        BROADCAST to send the content of the `Message` field using the Broadcast console command (NOTE: There is a bug in ASA which is preventing this from working with RCON at the moment)
        **MessageAtMinute** = This is a comma separated list of the number of minutes before the scheduled task time that messages will be sent E.g. If `Hour` = 16 and `Minute` = 30, setting this to 0 would send a message at 4:30 PM and setting it to 15 would send a message at 4:15 PM. Setting this to 0,5,15,30 would send 4 messages at 4:00 PM, 4:15 PM, 4:25 PM and 4:30 PM (*Subject to cron job timings, see below)
        **Message** = The message you want to send via the SeverChat or Broadcast console command. You can use \n for a new line.

> [!IMPORTANT]
> If you don't have your cron job (Scheduled Task in Windows) configured to run every minute, you must make sure your scheduled times are set at a time when the cron job will run.
> E.g. If you set a schedule to restart your server at 4:29 PM but your cron job runs at 4:25 and 4:30 PM, your task will not run and your server will not restart.