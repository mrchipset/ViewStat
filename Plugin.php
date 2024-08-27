<?php

namespace TypechoPlugin\ViewStat;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Db;
use Typecho\Request;
use Typecho\Cookie;
use Widget\Options;
use Utils\Helper;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * ViewStat
 *
 * @package ViewStat
 * @author Mr.Chip
 * @version 1.0.0
 * @link https://github.com/mrchipset/ViewStat
 */
class Plugin implements PluginInterface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        # check table existed.
        Plugin::install();
        # render total views
        \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::render';
        \Typecho\Plugin::factory('Widget_Archive')->afterRender = __CLASS__ . '::stats';
        Helper::addPanel(1, 'ViewStat/view-posts.php', _t('访问统计'), _t('访问统计'), 'administrator');
    }

    public static function install()
    {
        $sqls = <<<SQL
            CREATE TABLE IF NOT EXISTS typecho_view_summary(cid INTEGER NOT NULL PRIMARY KEY, views INTEGER NOT NULL default '0', updated int(10) default '0');
            CREATE TABLE IF NOT EXISTS typecho_view_details(vid INTEGER NOT NULL PRIMARY KEY, cid int(10) default '0', ipv4 varchar(16) default NULL, accessed int(10) default '0');
            CREATE INDEX typecho_view_details_cid ON typecho_view_details(cid);
        SQL;

        
        $db = Db::get();
        $prefix = $db->getPrefix();
        $sqls = str_replace('typecho_', $prefix, $sqls);

        try {
            $sqls = explode(';', $sqls);
            foreach ($sqls as $sql) {
                $sql = trim($sql);
                if ($sql) {
                    $db->query($sql, Db::WRITE);
                }
            }
        } catch (Db\Exception $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            echo 'Create faild with message:' .$msg .' Code' .$code;
        }
       
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate()
    {
        Helper::removePanel(1, 'ViewStat/view-posts.php');
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        /** 分类名称 */
        $name = new Text('word', null, 'Hello World', _t('说点什么'));
        $form->addInput($name);
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {
        echo '<span class="message success">'
            . ViewsCore::get_total_views()
            . ' views'
            . '</span>';
    }

    public static function stats($archive)
    {
        $type = $archive->getArchiveType();
        if ($type!='post') {
            return;
        }

        $cid = $archive->cid;
        $ipv4 = Request::getInstance()->getIp();
        $cookie_key = 'ViewStat_'.$cid;
        # check cookie
        $cookie_value = Cookie::get($cookie_key);
        if (!is_null($cookie_value)) {
            return;
        } else {
            # set cookie
            Cookie::set($cookie_key, 1, 8 * 60 * 60);
            # add view
            ViewsCore::add_cid_views($cid, $ipv4);
        }

    }
}
