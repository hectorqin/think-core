<?php

/**
 * 操纵文件类
 *
 * 例子：
 * FileUtil::createDir('a/1/2/3');                    测试建立文件夹 建一个a/1/2/3文件夹
 * FileUtil::createFile('b/1/2/3');                    测试建立文件        在b/1/2/文件夹下面建一个3文件
 * FileUtil::createFile('b/1/2/3.exe');             测试建立文件        在b/1/2/文件夹下面建一个3.exe文件
 * FileUtil::copyDir('b','d/e');                    测试复制文件夹 建立一个d/e文件夹，把b文件夹下的内容复制进去
 * FileUtil::copyFile('b/1/2/3.exe','b/b/3.exe'); 测试复制文件        建立一个b/b文件夹，并把b/1/2文件夹中的3.exe文件复制进去
 * FileUtil::moveDir('a/','b/c');                    测试移动文件夹 建立一个b/c文件夹,并把a文件夹下的内容移动进去，并删除a文件夹
 * FileUtil::moveFile('b/1/2/3.exe','b/d/3.exe'); 测试移动文件        建立一个b/d文件夹，并把b/1/2中的3.exe移动进去
 * FileUtil::unlinkFile('b/d/3.exe');             测试删除文件        删除b/d/3.exe文件
 * FileUtil::unlinkDir('d');                      测试删除文件夹 删除d文件夹
 */
class FileUtil
{
    static $localeSettings = array();

    /**
     * 获得pathinfo
     *
     * @param string $filepath
     * @return viod
     */
    public static function pathinfo($filepath, $type = null)
    {
        return self::mb_pathinfo($filepath, $type);
        self::localeSettings("backup");
        self::localeSettings("fix");
        $result = is_null($type) ? pathinfo($filepath) : pathinfo($filepath, $type);
        self::localeSettings("restore");
        return $result;
    }

    public static function localeSettings($type)
    {
        switch ($type) {
            case 'backup':
                $localeSettings = setlocale(LC_ALL, 0);
                if (strpos($localeSettings, ";") === false) {
                    self::$localeSettings["LC_ALL"] = $localeSettings;
                }
                // If any of the locales differs, then setlocale() returns all the locales separated by semicolon
                // Eg: LC_CTYPE=it_IT.UTF-8;LC_NUMERIC=C;LC_TIME=C;...
                else {
                    $locales = explode(";", $localeSettings);
                    foreach ($locales as $locale) {
                        list($key, $value)          = explode("=", $locale);
                        self::$localeSettings[$key] = $value;
                    }
                }
                break;
            case 'restore':
                foreach (self::$localeSettings as $key => $value) {
                    setlocale(constant($key), $value);
                }
                break;
            case "fix":
                setlocale(LC_ALL, "C.UTF-8");
                break;
            default:
                break;
        }
    }

    public static function mb_pathinfo($filepath, $type = null)
    {
        preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $filepath, $m);
        switch ($type) {
            case PATHINFO_DIRNAME:
                return isset($m[1]) ? $m[1] : '';
                break;
            case PATHINFO_BASENAME:
                return isset($m[2]) ? $m[2] : '';
                break;
            case PATHINFO_FILENAME:
                return isset($m[3]) ? $m[3] : '';
                break;
            case PATHINFO_EXTENSION:
                return isset($m[5]) ? $m[5] : '';
                break;
            default:
                return array(
                    'dirname'   => isset($m[1]) ? $m[1] : '',
                    'basename'  => isset($m[2]) ? $m[2] : '',
                    'filename'  => isset($m[3]) ? $m[3] : '',
                    'extension' => isset($m[5]) ? $m[5] : '',
                );
                break;
        }
    }

    /**
     * 建立文件夹
     *
     * @param string $aimUrl
     * @return viod
     */
    public static function createDir($aimUrl)
    {
        $aimUrl  = str_replace('', '/', $aimUrl);
        $aimDir  = '';
        $subDirs = explode('/', $aimUrl);
        foreach ($subDirs as $dir) {
            $aimDir .= $dir . '/';
            if (!file_exists($aimDir) && !mkdir($aimDir)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 建立文件
     *
     * @param string $aimUrl
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public static function createFile($aimUrl, $overWrite = false)
    {
        if (file_exists($aimUrl)) {
            return $overWrite ? FileUtil::unlinkFile($aimUrl) : false;
        }
        if (!FileUtil::createDir(self::pathinfo($aimUrl, PATHINFO_DIRNAME))) {
            return false;
        }
        return touch($aimUrl);
    }

    /**
     * 移动文件夹
     *
     * @param string $oldDir
     * @param string $aimDir
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public static function moveDir($oldDir, $aimDir, $overWrite = false)
    {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir) && !FileUtil::createDir($aimDir)) {
            return false;
        }
        @$dirHandle = opendir($oldDir);
        if (!$dirHandle) {
            return false;
        }
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir . $file)) {
                FileUtil::moveFile($oldDir . $file, $aimDir . $file, $overWrite);
            } else {
                FileUtil::moveDir($oldDir . $file, $aimDir . $file, $overWrite);
            }
        }
        closedir($dirHandle);
        return rmdir($oldDir);
    }

    /**
     * 移动文件
     *
     * @param string $fileUrl
     * @param string $aimUrl
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public static function moveFile($fileUrl, $aimUrl, $overWrite = false)
    {
        if (!file_exists($fileUrl)) {
            return false;
        }
        if (file_exists($aimUrl) && $overWrite = false) {
            return false;
        }
        if (!FileUtil::createDir(self::pathinfo($aimUrl, PATHINFO_DIRNAME))) {
            return false;
        }
        return rename($fileUrl, $aimUrl);
    }

    /**
     * 删除文件夹
     *
     * @param string $aimDir
     * @return boolean
     */
    public static function unlinkDir($aimDir)
    {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        if (!is_dir($aimDir)) {
            return false;
        }
        $dirHandle = opendir($aimDir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($aimDir . $file)) {
                FileUtil::unlinkFile($aimDir . $file);
            } else {
                FileUtil::unlinkDir($aimDir . $file);
            }
        }
        closedir($dirHandle);
        return rmdir($aimDir);
    }

    /**
     * 删除文件
     *
     * @param string $aimUrl
     * @return boolean
     */
    public static function unlinkFile($aimUrl)
    {
        return file_exists($aimUrl) ? unlink($aimUrl) : false;
    }

    /**
     * 复制文件夹
     *
     * @param string $oldDir
     * @param string $aimDir
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public static function copyDir($oldDir, $aimDir, $overWrite = false)
    {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir) && !FileUtil::createDir($aimDir)) {
            return false;
        }
        $dirHandle = opendir($oldDir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir . $file)) {
                FileUtil::copyFile($oldDir . $file, $aimDir . $file, $overWrite);
            } else {
                FileUtil::copyDir($oldDir . $file, $aimDir . $file, $overWrite);
            }
        }
        return closedir($dirHandle);
    }

    /**
     * 复制文件
     *
     * @param string $fileUrl
     * @param string $aimUrl
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public static function copyFile($fileUrl, $aimUrl, $overWrite = false)
    {
        if (!file_exists($fileUrl)) {
            return false;
        }
        if (file_exists($aimUrl) && $overWrite == false) {
            return false;
        }
        if (!FileUtil::createDir(self::pathinfo($aimUrl, PATHINFO_DIRNAME))) {
            return false;
        }
        return copy($fileUrl, $aimUrl);
    }
}

// FileUtil::createDir("/Users/aa/Desktop/htdocs/ws/media/workspace/65/");
// print_r(FileUtil::pathinfo("/Users/aa/Desktop/htdocs/ws/media/workspace/65/a"));
// touch("aa/1.txt");
// touch("aa/2.txt");
// copy("aa/1.txt", "aa/2.txt");
