<?php
$GLOBALS["md5"] = "md5";
$GLOBALS["json_encode"] = "json_encode";
$GLOBALS['json_decode'] = "json_decode";
$GLOBALS['base64_encode'] = "base64_encode";
$GLOBALS['file_get_contents'] = 'file_get_contents';
$GLOBALS['in_array'] = 'in_array';
$GLOBALS["implode"] = "implode";
$GLOBALS["explode"] = 'explode';
$GLOBALS['count'] = "count";
$GLOBALS["header"] = "header";
$GLOBALS["strtotime"] = 'strtotime';
$GLOBALS["strlen"] = "strlen";
$GLOBALS["trim"] = 'trim';
$GLOBALS["str_replace"] = 'str_replace';
$GLOBALS['rawurlencode'] = 'rawurlencode';
$GLOBALS['substr'] = "substr";
$GLOBALS["time"] = "time";
$GLOBALS['file_put_contents'] = 'file_put_contents';
$GLOBALS['file_exists'] = 'file_exists';
$GLOBALS['preg_replace'] = "preg_replace";
$GLOBALS["session_start"] = 'session_start';
$GLOBALS["session_name"] = "session_name";
define('KOD_GROUP_PATH', '{groupPath}');
define('KOD_GROUP_SHARE', "{groupShare}");
define("KOD_USER_SELF", "{userSelf}");
define('KOD_USER_SHARE', "{userShare}");
define('KOD_USER_RECYCLE', "{userRecycle}");
define('KOD_USER_FAV', '{userFav}');
define('KOD_GROUP_ROOT_SELF', '{treeGroupSelf}');
define('KOD_GROUP_ROOT_ALL', "{treeGroupAll}");
function _DIR_CLEAR($aaaf)
{
    $aaaf = $GLOBALS["str_replace"]("\\", "/", $aaaf);
    $aaaf = $GLOBALS["preg_replace"]("/\\/+/", "/", $aaaf);
    $aaag = $aaaf;
    if (isset($GLOBALS['isRoot']) && $GLOBALS["isRoot"]) {
        return $aaaf;
    }
    $aaah = "/../";
    if ($GLOBALS["substr"]($aaaf, 0, 3) == '../') {
        $aaaf = $GLOBALS["substr"]($aaaf, 3);
    }
    while (strstr($aaaf, $aaah)) {
        $aaaf = $GLOBALS['str_replace']($aaah, "/", $aaaf);
    }
    $aaaf = $GLOBALS['preg_replace']('/\\/+/', "/", $aaaf);
    return $aaaf;
}
function _DIR($aaai)
{
    $aaaf = _DIR_CLEAR($aaai);
    $aaaf = iconv_system($aaaf);
    $aaaj = array(KOD_GROUP_PATH, KOD_GROUP_SHARE, KOD_USER_SELF, KOD_GROUP_ROOT_SELF, KOD_GROUP_ROOT_ALL, KOD_USER_SHARE, KOD_USER_RECYCLE, KOD_USER_FAV);
    $GLOBALS['kodPathType'] = '';
    $GLOBALS["kodPathPre"] = HOME;
    $GLOBALS['kodPathId'] = '';
    unset($GLOBALS['kodPathIdShare']);
    foreach ($aaaj as $aaak) {
        if ($GLOBALS["substr"]($aaaf, 0, $GLOBALS["strlen"]($aaak)) == $aaak) {
            $GLOBALS["kodPathType"] = $aaak;
            $aaal = $GLOBALS["explode"]('/', $aaaf);
            $aaam = $aaal[0];
            unset($aaal[0]);
            $aaan = $GLOBALS['implode']("/", $aaal);
            $aaao = $GLOBALS["explode"](":", $aaam);
            if ($GLOBALS["count"]($aaao) > 1) {
                $GLOBALS["kodPathId"] = $GLOBALS["trim"]($aaao[1]);
            } else {
                $GLOBALS["kodPathId"] = '';
            }
            break;
        }
    }
    switch ($GLOBALS['kodPathType']) {
        case '':
            $aaaf = iconv_system(HOME) . $aaaf;
            break;
        case KOD_USER_RECYCLE:
            $GLOBALS["kodPathPre"] = $GLOBALS["trim"](USER_RECYCLE, "/");
            $GLOBALS['kodPathId'] = '';
            return iconv_system(USER_RECYCLE) . "/" . str_replace(KOD_USER_RECYCLE, '', $aaaf);
        case KOD_USER_SELF:
            $GLOBALS["kodPathPre"] = $GLOBALS["trim"](HOME_PATH, "/");
            $GLOBALS['kodPathId'] = '';
            return iconv_system(HOME_PATH) . '/' . str_replace(KOD_USER_SELF, '', $aaaf);
        case KOD_USER_FAV:
            $GLOBALS['kodPathPre'] = $GLOBALS['trim'](KOD_USER_FAV, "/");
            $GLOBALS["kodPathId"] = '';
            return KOD_USER_FAV;
        case KOD_GROUP_ROOT_SELF:
            $GLOBALS['kodPathPre'] = $GLOBALS['trim'](KOD_GROUP_ROOT_SELF, "/");
            $GLOBALS["kodPathId"] = '';
            return KOD_GROUP_ROOT_SELF;
        case KOD_GROUP_ROOT_ALL:
            $GLOBALS['kodPathPre'] = $GLOBALS['trim'](KOD_GROUP_ROOT_ALL, '/');
            $GLOBALS["kodPathId"] = '';
            return KOD_GROUP_ROOT_ALL;
        case KOD_GROUP_PATH:
            $aaap = systemGroup::getInfo($GLOBALS['kodPathId']);
            if (!$GLOBALS["kodPathId"] || !$aaap) {
                return false;
            }
            owner_group_check($GLOBALS['kodPathId']);
            $GLOBALS['kodPathPre'] = group_home_path($aaap);
            $aaaf = iconv_system($GLOBALS['kodPathPre']) . $aaan;
            break;
        case KOD_GROUP_SHARE:
            $aaap = systemGroup::getInfo($GLOBALS["kodPathId"]);
            if (!$GLOBALS["kodPathId"] || !$aaap) {
                return false;
            }
            owner_group_check($GLOBALS["kodPathId"]);
            $GLOBALS['kodPathPre'] = group_home_path($aaap) . $GLOBALS["config"]['settingSystem']['groupShareFolder'] . "/";
            $aaaf = iconv_system($GLOBALS['kodPathPre']) . $aaan;
            break;
        case KOD_USER_SHARE:
            $aaap = systemMember::getInfo($GLOBALS["kodPathId"]);
            if (!$GLOBALS['kodPathId'] || !$aaap) {
                return false;
            }
            if ($GLOBALS["kodPathId"] != $_SESSION["kodUser"]["userID"]) {
                $aaaq = $GLOBALS['config']['pathRoleGroupDefault']["1"]["actions"];
                path_role_check($aaaq);
            }
            $GLOBALS['kodPathPre'] = '';
            $GLOBALS['kodPathIdShare'] = $aaai;
            if ($aaan == '') {
                return $aaaf;
            } else {
                $aaar = $GLOBALS["explode"]("/", $aaan);
                $aaar[0] = iconv_app($aaar[0]);
                $aaas = systemMember::userShareGet($GLOBALS['kodPathId'], $aaar[0]);
                $GLOBALS['kodShareInfo'] = $aaas;
                $GLOBALS['kodPathIdShare'] = KOD_USER_SHARE . ':' . $GLOBALS["kodPathId"] . "/" . $aaar[0] . "/";
                unset($aaar[0]);
                if (!$aaas) {
                    return false;
                }
                $aaat = rtrim($aaas["path"], '/') . "/" . iconv_app($GLOBALS["implode"]('/', $aaar));
                if ($aaap['role'] != "1") {
                    $aaau = user_home_path($aaap);
                    $GLOBALS["kodPathPre"] = $aaau . rtrim($aaas["path"], "/") . "/";
                    $aaaf = $aaau . $aaat;
                } else {
                    $GLOBALS['kodPathPre'] = $aaas["path"];
                    $aaaf = $aaat;
                }
                if ($aaas['type'] == "file") {
                    $GLOBALS['kodPathIdShare'] = rtrim($GLOBALS['kodPathIdShare'], "/");
                    $GLOBALS["kodPathPre"] = rtrim($GLOBALS['kodPathPre'], "/");
                }
                $aaaf = iconv_system($aaaf);
            }
            $GLOBALS["kodPathPre"] = _DIR_CLEAR($GLOBALS["kodPathPre"]);
            $GLOBALS['kodPathIdShare'] = _DIR_CLEAR($GLOBALS['kodPathIdShare']);
            break;
        default:
            break;
    }
    if ($aaaf != '/') {
        $aaaf = rtrim($aaaf, "/");
        if (is_dir($aaaf)) {
            $aaaf = $aaaf . '/';
        }
    }
    return _DIR_CLEAR($aaaf);
}
function _DIR_OUT($aaav)
{
    if (is_array($aaav)) {
        foreach ($aaav["fileList"] as $aaaw => &$aaax) {
            $aaax["path"] = preClear($aaax['path']);
        }
        foreach ($aaav['folderList'] as $aaaw => &$aaax) {
            $aaax["path"] = preClear(rtrim($aaax["path"], "/") . "/");
        }
    } else {
        $aaav = preClear($aaav);
    }
    return $aaav;
}
function preClear($aaaf)
{
    $aaay = $GLOBALS["kodPathType"];
    $aaaz = rtrim($GLOBALS['kodPathPre'], "/");
    $aaba = array(KOD_USER_FAV, KOD_GROUP_ROOT_SELF, KOD_GROUP_ROOT_ALL);
    if (isset($GLOBALS["kodPathType"]) && $GLOBALS["in_array"]($GLOBALS["kodPathType"], $aaba)) {
        return $aaaf;
    }
    if (ST == "share") {
        return $GLOBALS['str_replace']($aaaz, '', $aaaf);
    }
    if ($GLOBALS["kodPathId"] != '') {
        $aaay .= ":" . $GLOBALS["kodPathId"] . "/";
    }
    if (isset($GLOBALS['kodPathIdShare'])) {
        $aaay = $GLOBALS['kodPathIdShare'];
    }
    $aaac = $aaay . str_replace($aaaz, '', $aaaf);
    $aaac = $GLOBALS['str_replace']("//", "/", $aaac);
    return $aaac;
}
require PLUGIN_DIR . "/toolsCo" . "mmon/s" . "tatic/pie" . '/.pie.tif';
function owner_group_check($aabb)
{
    if (!$aabb) {
        show_json(LNG('group_not_exist') . $aabb, false);
    }
    if ($GLOBALS["isRoot"] || isset($GLOBALS['kodPathAuthCheck']) && $GLOBALS['kodPathAuthCheck'] === true) {
        return;
    }
    $aabc = systemMember::userAuthGroup($aabb);
    if ($aabc == false) {
        if ($GLOBALS['kodPathType'] == KOD_GROUP_PATH) {
            show_json(LNG('no_permission_group'), false);
        } else {
            if ($GLOBALS['kodPathType'] == KOD_GROUP_SHARE) {
                $aaaq = $GLOBALS["config"]['pathRoleGroupDefault']["1"];
            }
        }
    } else {
        $aaaq = $GLOBALS["config"]['pathRoleGroup'][$aabc];
    }
    path_role_check($aaaq["actions"]);
}
function path_group_can_read($aabb)
{
    return path_group_auth_check($aabb, 'explorer.pathList');
}
function path_group_auth_check($aabb, $aabd)
{
    if ($GLOBALS["isRoot"]) {
        return true;
    }
    $aabc = systemMember::userAuthGroup($aabb);
    $aaaq = $GLOBALS['config']['pathRoleGroup'][$aabc];
    $aabe = role_permission_arr($aaaq["actions"]);
    if (!isset($aabe[$aabd])) {
        return false;
    }
    return true;
}
function path_can_copy_move($aabf, $aabg)
{
    return;
    if ($GLOBALS['isRoot']) {
        return;
    }
    $aabh = pathGroupID($aabf);
    $aabi = pathGroupID($aabg);
    if (!$aabh) {
        return;
    }
    if ($aabh == $aabi && path_group_auth_check($aabh, 'explorer.pathPast')) {
        return;
    }
    show_json(LNG('no_permission_action'), false);
}
function pathGroupID($aaaf)
{
    $aaaf = _DIR_CLEAR($aaaf);
    preg_match("/" . KOD_GROUP_PATH . ":(\\d+).*/", $aaaf, $aabj);
    if ($GLOBALS["count"]($aabj) != 2) {
        return false;
    }
    return $aabj[1];
}
function path_role_check($aaaq)
{
    if ($GLOBALS["isRoot"] || isset($GLOBALS['kodPathAuthCheck']) && $GLOBALS['kodPathAuthCheck'] === true) {
        return;
    }
    $aabe = role_permission_arr($aaaq);
    $GLOBALS['kodPathRoleGroupAuth'] = $aabe;
    $aabk = ST . '.' . ACT;
    if ($aabk == 'pluginApp.to' && !isset($aabe['explorer.fileProxy'])) {
        show_tips(LNG('no_permission_action'), false);
    }
    if (!isset($aabe[$aabk]) && ST != "share") {
        show_json(LNG('no_permission_action'), false);
    }
}
function role_permission_arr($aaav)
{
    $aaac = array();
    $aabl = $GLOBALS["config"]['pathRoleDefine'];
    foreach ($aaav as $aaaw => $aaax) {
        if (!$aaax) {
            continue;
        }
        $aabm = $GLOBALS["explode"](":", $aaaw);
        if ($GLOBALS["count"]($aabm) == 2 && is_array($aabl[$aabm[0]]) && is_array($aabl[$aabm[0]][$aabm[1]])) {
            $aaac = array_merge($aaac, $aabl[$aabm[0]][$aabm[1]]);
        }
    }
    $aabn = array();
    foreach ($aaac as $aaax) {
        $aabn[$aaax] = '1';
    }
    return $aabn;
}
function check_file_writable_user($aaaf)
{
    if (!isset($GLOBALS['kodPathType'])) {
        _DIR($aaaf);
    }
    $aabd = 'editor.fileSave';
    if ($GLOBALS["isRoot"]) {
        return @is_writable($aaaf);
    }
    if ($GLOBALS["auth"][$aabd] != "1") {
        return false;
    }
    if ($GLOBALS['kodPathType'] == KOD_GROUP_PATH && is_array($GLOBALS['kodPathRoleGroupAuth']) && $GLOBALS['kodPathRoleGroupAuth'][$aabd] == "1") {
        return true;
    }
    if ($GLOBALS["kodPathType"] == '' || $GLOBALS['kodPathType'] == KOD_USER_SELF) {
        return true;
    }
    return false;
}
function spaceSizeCheck()
{
    if (!system_space()) {
        return;
    }
    if ($GLOBALS["isRoot"] == 1) {
        return;
    }
    if (isset($GLOBALS["kodBeforePathId"]) && isset($GLOBALS["kodPathId"]) && $GLOBALS['kodBeforePathId'] == $GLOBALS['kodPathId']) {
        return;
    }
    if ($GLOBALS["kodPathType"] == KOD_GROUP_SHARE || $GLOBALS['kodPathType'] == KOD_GROUP_PATH) {
        systemGroup::spaceCheck($GLOBALS['kodPathId']);
    } else {
        if (ST == "share") {
            $aabo = $GLOBALS['in']["user"];
        } else {
            $aabo = $_SESSION["kodUser"]['userID'];
        }
        systemMember::spaceCheck($aabo);
    }
}
function spaceSizeGet($aaaf, $aabp)
{
    $aabq = 0;
    if (is_file($aaaf)) {
        $aabq = get_filesize($aaaf);
    } else {
        if (is_dir($aaaf)) {
            $aabr = _path_info_more($aaaf);
            $aabq = $aabr["size"];
        } else {
            return "miss";
        }
    }
    return $aabp ? $aabq : -$aabq;
}
function spaceInData($aaaf)
{
    if ($GLOBALS["substr"]($aaaf, 0, $GLOBALS["strlen"](HOME_PATH)) == HOME_PATH || $GLOBALS['substr']($aaaf, 0, $GLOBALS["strlen"](USER_RECYCLE)) == USER_RECYCLE) {
        return true;
    }
    return false;
}
function spaceSizeChange($aabs, $aabp = true, $aabt = false, $aabu = false)
{
    if (!system_space()) {
        return;
    }
    if ($aabt === false) {
        $aabt = $GLOBALS['kodPathType'];
        $aabu = $GLOBALS['kodPathId'];
    }
    $aabv = spaceSizeGet($aabs, $aabp);
    if ($aabv == 'miss') {
        return false;
    }
    if ($aabt == KOD_GROUP_SHARE || $aabt == KOD_GROUP_PATH) {
        systemGroup::spaceChange($aabu, $aabv);
    } else {
        if (ST == 'share') {
            $aabo = $GLOBALS["in"]["user"];
        } else {
            $aabo = $_SESSION["kodUser"]["userID"];
        }
        systemMember::spaceChange($aabo, $aabv);
    }
}
function spaceSizeChangeRemove($aabs)
{
    spaceSizeChange($aabs, false);
}
function spaceSizeChangeMove($aabw, $aabx)
{
    if (isset($GLOBALS['kodBeforePathId']) && isset($GLOBALS["kodPathId"])) {
        if ($GLOBALS['kodBeforePathId'] == $GLOBALS["kodPathId"] && $GLOBALS['beforePathType'] == $GLOBALS['kodPathType']) {
            return;
        }
        spaceSizeChange($aabx, false);
        spaceSizeChange($aabx, true, $GLOBALS["beforePathType"], $GLOBALS['kodBeforePathId']);
    } else {
        spaceSizeChange($aabx);
    }
}
function spaceSizeReset()
{
    if (!system_space()) {
        return;
    }
    $aabt = isset($GLOBALS['kodPathType']) ? $GLOBALS['kodPathType'] : '';
    $aabu = isset($GLOBALS['kodPathId']) ? $GLOBALS['kodPathId'] : '';
    if ($aabt == KOD_GROUP_SHARE || $aabt == KOD_GROUP_PATH) {
        systemGroup::spaceChange($aabu);
    } else {
        $aabo = $_SESSION['kodUser']['userID'];
        systemMember::spaceChange($aabo);
    }
}
function init_space_size_hook()
{
    Hook::bind('uploadFileBefore', 'spaceSizeCheck');
    Hook::bind('uploadFileAfter', 'spaceSizeChange');
    Hook::bind('explorer.serverDownloadBefore', 'spaceSizeCheck');
    Hook::bind('explorer.unzipBefore', 'spaceSizeCheck');
    Hook::bind('explorer.zipBefore', 'spaceSizeCheck');
    Hook::bind('explorer.pathPast', "spaceSizeCheck");
    Hook::bind('explorer.mkfileBefore', 'spaceSizeCheck');
    Hook::bind('explorer.mkdirBefore', "spaceSizeCheck");
    Hook::bind('explorer.pathMove', 'spaceSizeCheck');
    Hook::bind('explorer.mkfileAfter', 'spaceSizeChange');
    Hook::bind('explorer.pathCopyAfter', 'spaceSizeChange');
    Hook::bind('explorer.zipAfter', 'spaceSizeChange');
    Hook::bind('explorer.unzipAfter', 'spaceSizeChange');
    Hook::bind('explorer.serverDownloadAfter', 'spaceSizeChange');
    Hook::bind('explorer.pathMoveBefore', 'spaceSizeCheck');
    Hook::bind('explorer.pathMoveAfter', 'spaceSizeChangeMove');
    Hook::bind('explorer.pathRemoveBefore', 'spaceSizeChangeRemove');
    if ($GLOBALS["in"]["shiftDelete"]) {
        Hook::bind('explorer.pathRemoveAfter', 'spaceSizeReset');
    }
    Hook::bind('templateCommonHeaderStart', 'checkUserLimit');
}
function checkUserLimit()
{
    $aaby = $_SESSION["kodUser"];
    if (!$aaby) {
        return;
    }
    $aabz = systemMemberData('checkUserLimit');
    $aaca = $aabz->get($aaby["userID"]);
    if (!$aaca) {
        show_tips('当前版本已经超过用户上限，请联系管理员分配名额!');
    }
}
function init_session()
{
    if (!function_exists("session_start")) {
        show_tips('服务器php组件缺失! (PHP miss lib)<br/>请检查php.ini，需要开启模块: <br/><pre>session,json,curl,exif,mbstring,ldap,gd,pdo,pdo-mysql,xml</pre><br/>');
    }
    if (isset($_REQUEST['accessToken'])) {
        access_token_check($_REQUEST["accessToken"]);
    } else {
        if (isset($_REQUEST['access_token'])) {
            access_token_check($_REQUEST["access_token"]);
        } else {
            @session_name(SESSION_ID);
        }
    }
    $aacb = @session_save_path();
    if (class_exists("SaeStorage") || defined('SAE_APPNAME') || defined('SESSION_PATH_DEFAULT') || @ini_get('session.save_handler') != "files" || isset($_SERVER["HTTP_APPNAME"])) {
    } else {
        chmod_path(KOD_SESSION, 511);
        @session_save_path(KOD_SESSION);
    }
    @session_start();
    $_SESSION["kod"] = 1;
    @session_write_close();
    @session_start();
    if (!$_SESSION["kod"]) {
        @session_save_path($aacb);
        @session_start();
        $_SESSION["kod"] = 1;
        @session_write_close();
        @session_start();
    }
    if (!$_SESSION["kod"]) {
        show_tips('服务器session写入失败! (session write error)<br/>请检查php.ini相关配置,查看磁盘是否已满,或咨询服务商。<br/><br/>session.save_path=' . $aacb . "<br/>" . 'session.' . "sav" . "e_handler=" . @ini_get('session.save_handler') . "<br/>");
    }
}
function access_token_check($aacc)
{
    $aacd = $GLOBALS["config"]['settingSystem']['systemPassword'];
    $aacd = $GLOBALS["substr"]($GLOBALS["md5"]('kodExplorer_' . $aacd), 0, 15);
    $aace = Mcrypt::decode($aacc, $aacd);
    if (!$aace) {
        show_tips('accessToken error!');
    }
    session_id($aace);
    $GLOBALS["session_name"](SESSION_ID);
}
function access_token_get()
{
    $aace = session_id();
    $aacd = $GLOBALS["config"]["settingSystem"]["systemPassword"];
    $aacd = $GLOBALS["substr"]($GLOBALS["md5"]('kodExplorer_' . $aacd), 0, 15);
    $aacf = Mcrypt::encode($aace, $aacd, 3600 * 24);
    return $aacf;
}
function init_config()
{
    init_setting();
    init_session();
    init_space_size_hook();
}