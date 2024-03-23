<?php
class NitrAutoConfig
{
	public const DEBUG = false;

	public const Host = "localhost"; //localhost can be used if the MySQL server is running on the same machine as your web server. Otherwise enter the fomain name or IP addres of your MySQL server
	public const Username = "Your_MySQL_Username";
	public const Password = "Your_MySQL_Password";
	public const Database = "Your_SQL_Database_Name";
	public const NitrAPIToken = "Your_Nitrado_Long_Life_token";
	public const RCONPassword = "Your_RCON_Password";
	public const DynamicConfigFile = "dynamicconfig.ini";
    public const CRONIntervalSeconds = 60; //How often your cron job runs in seconds
    public const RCONTimeout = 10; //Seconds
}
?>