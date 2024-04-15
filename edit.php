<?php
function dos2unix($cmd){
    $cmd = str_replace("\r", "", $cmd); //same dos2unix
    return $cmd;
}
//$cfg={slug,name,team_id,package,profile_uuid}
function edit_files($target,$cfg=[]) {
	//project.pbxproj
	$f = "$target/platforms/ios/{$cfg['name']}.xcodeproj/project.pbxproj";
	$str = dos2unix(file_get_contents($f));

	$add = <<<EOF
CODE_SIGN_IDENTITY = "Apple Development";
				CODE_SIGN_STYLE = Manual;
				DEVELOPMENT_TEAM = {$cfg['team_id']};
				INFOPLIST_KEY_LSApplicationCategoryType = "public.app-category.games";
				PROVISIONING_PROFILE_SPECIFIER = "";
EOF;
	$s0 = "/* build-debug.xcconfig */;\n\t\t\tbuildSettings = {\n\t\t\t\tALWAYS_SEARCH_USER_PATHS = NO;\n\t\t\t\tASSETCATALOG_COMPILER_APPICON_NAME = AppIcon;\n\t\t\t\tCLANG_ENABLE_MODULES = YES;\n\t\t\t\tCLANG_ENABLE_OBJC_ARC = YES;";
	if(substr_count($str, $s0)>0) $str = str_replace($s0, $s0."\n\t\t\t\t$add", $str);
	else echo "\033[33mNot found: $s0\033[0m\n";

	$add = <<<EOF
CODE_SIGN_IDENTITY = "Apple Development";
				"CODE_SIGN_IDENTITY[sdk=iphoneos*]" = "iPhone Distribution";
				CODE_SIGN_STYLE = Manual;
				DEVELOPMENT_TEAM = {$cfg['team_id']};
				"DEVELOPMENT_TEAM[sdk=iphoneos*]" = {$cfg['team_id']};
				INFOPLIST_KEY_LSApplicationCategoryType = "public.app-category.games";
				PROVISIONING_PROFILE_SPECIFIER = "";
				"PROVISIONING_PROFILE_SPECIFIER[sdk=iphoneos*]" = {$cfg['slug']};
EOF;
	$s0="/* build-release.xcconfig */;\n\t\t\tbuildSettings = {\n\t\t\t\tALWAYS_SEARCH_USER_PATHS = NO;\n\t\t\t\tASSETCATALOG_COMPILER_APPICON_NAME = AppIcon;\n\t\t\t\tCLANG_ENABLE_MODULES = YES;\n\t\t\t\tCLANG_ENABLE_OBJC_ARC = YES;";
	if(substr_count($str, $s0)>0) $str = str_replace($s0, $s0."\n\t\t\t\t$add", $str);
	else echo "\033[33mNot found: $s0\033[0m\n";

	$s0="/* build.xcconfig */;\n\t\t\tbuildSettings = {";
	if(substr_count($str, $s0)>0) $str = str_replace($s0,$s0."\n\t\t\t\tCODE_SIGN_STYLE = Manual;", $str);
	else echo "\033[33mNot found: $s0\033[0m\n";

	file_put_contents($f, $str);
	echo "> edit $f\n";

	//exportOptions.plist
	$f="$target/platforms/ios/exportOptions.plist";
	$txt=<<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
  <dict>
    <key>compileBitcode</key>
    <false/>
    <key>method</key>
    <string>app-store</string>
    <key>teamID</key>
    <string>[team_id]</string>
    <key>provisioningProfiles</key>
    <dict>
      <key>[package]</key>
      <string>[uuid]</string>
    </dict>
    <key>signingStyle</key>
    <string>manual</string>
    <key>signingCertificate</key>
    <string>iPhone Distribution</string>
  </dict>
</plist>
EOF;
	$txt = str_replace('[package]',$cfg['package'], $txt);
	$txt = str_replace('[team_id]',$cfg['team_id'], $txt);
	$txt = str_replace('[uuid]',$cfg['profile_uuid'], $txt);

	file_put_contents($f, $txt);
	echo "> edit $f\n";

	//Info.plist
	$f="$target/platforms/ios/{$cfg['name']}/{$cfg['name']}-Info.plist";
	$str = dos2unix(file_get_contents($f));
	$s0="<plist version=\"1.0\">\n<dict>";
	if(substr_count($str, $s0)>0) $str = str_replace($s0, $s0."\n\t<key>ITSAppUsesNonExemptEncryption</key>\n\t<false/>", $str);
	else echo "\033[33mNot found: $s0\033[0m\n";

	file_put_contents($f, $str);
	echo "> edit $f\n";

	//ios/cordova/build-extras.xcconfig
	$f = "$target/platforms/ios/cordova/build-extras.xcconfig";
	$txt=<<<EOF
CODE_SIGN_IDENTITY = iPhone Developer
CODE_SIGN_IDENTITY[sdk=iphoneos*] = iPhone Developer
PROVISIONING_PROFILE_SPECIFIER = [uuid]
DEVELOPMENT_TEAM = [team_id]
EOF;
	$txt = str_replace('[uuid]',$cfg['profile_uuid'], $txt);
	$txt = str_replace('[team_id]',$cfg['team_id'], $txt);
	file_put_contents($f, $txt);
	echo "> edit $f\n";
}
$slug = $argv[1];
$name = $argv[2];
$package = $argv[3];
$team_id = $argv[4];
$profile_id = $argv[5];
$target = $argv[6];

$cfg = [
	"slug"=> $slug,
    "name"=> $name,
    "package"=> $package,
    "team_id"=> $team_id,
    "profile_uuid"=> $profile_id,
];
//$cfg = json_decode(file_get_contents(__DIR__."/game.json"),true);
#$target = "~/Downloads/{$cfg['slug']}";

if(is_dir($target)) {
	edit_files($target, $cfg);
}
else {
	echo "\033[31mNo exist path {$target}\033[0m";
}
