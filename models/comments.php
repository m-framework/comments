<?php

namespace modules\comments\models;

use m\i18n;
use m\model;
use modules\pages\models\pages;
use modules\users\models\users;
use modules\users\models\users_info;

class comments extends model
{
    public $_table = 'comments';
    protected $_sort = ['date' => 'DESC'];

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'page' => 'int',
        'related_model' => 'varchar',
        'related_id' => 'int',
        'reply' => 'int',
        'author' => 'int',
        'comment' => 'text',
        'date' => 'timestamp',
        'date_edited' => 'timestamp',
    ];

    public function _before_destroy()
    {
        $chlids = $this->s([],['reply' => $this->id], [10000])->all('object');

        if (!empty($chlids)) {
            foreach ($chlids as $chlid) {
                $chlid->destroy();
            }
        }

        return true;
    }

    public function _autoload_user()
    {
        $this->user = users_info::call_static()->s([], ['profile' => $this->author])->obj();
    }

    public function _autoload_name()
    {
        return $this->name = $this->user->name;
    }

    public function _autoload_avatar()
    {
        return $this->avatar = $this->user->avatar;
    }

    public function _autoload_beauty_date()
    {
        if (empty($this->date)) {
            return '';
        }
        $time = strtotime($this->date);
        $this->beauty_date = date('Y', $time) !== date('Y') ? strftime('%e %b %Y %H:%M', $time)
            : strftime('%e %b %H:%M', $time);
        return $this->beauty_date;
    }

    public function _autoload_beauty_date_edited()
    {
        if (empty($this->date_edited)) {
            return '';
        }

        $time = strtotime($this->date_edited);

        $this->beauty_date_edited = ' (*edited* ' . (date('Y.m.d', $time) !== date('Y.m.d', strtotime($this->date))
            ? strftime('%e %b %H:%M', $time) : '*at* ' . strftime('%H:%M', $time)) . ')';

        return $this->beauty_date_edited;
    }

    public function _autoload_target_arr()
    {
        $this->target_arr = [];

        $page = new pages($this->page);

        $target_path = $page->get_path();
        $target_name = i18n::get($page->name);

        $this->target_arr['page_path'] = $target_path;
        $this->target_arr['page_name'] = $target_name;

        if (!empty($this->related_model)) {
            switch($this->related_model) {
                case 'articles':
                    if (class_exists('\modules\articles\models\articles')) {
                        $article = new \modules\articles\models\articles($this->related_id);
                        if (!empty($article->id)) {
                            $target_path .= '/' . $article->alias;
                            $target_name = $article->title;
                        }
                    }
                break;
                case 'shop_products':
                    if (class_exists('\modules\shop\models\shop_products')) {
                        $article = new \modules\shop\models\shop_products($this->related_id);
                        if (!empty($article->id)) {
                            $target_path .= '/' . $article->alias;
                            $target_name = $article->title;
                        }
                    }
                break;
            }
        }

        $this->target_arr['path'] = $target_path;
        $this->target_arr['name'] = $target_name;

        return $this->target_arr;
    }

    public function _autoload_page_path()
    {
        $this->page_path = $this->target_arr['page_path'];
        return $this->page_path;
    }

    public function _autoload_page_name()
    {
        $this->page_name = $this->target_arr['page_name'];
        return $this->page_name;
    }

    public function _autoload_target_path()
    {
        $this->target_path = $this->target_arr['path'];
        return $this->target_path;
    }

    public function _autoload_target_name()
    {
        $this->target_name = $this->target_arr['name'];
        return $this->target_name;
    }

    public function _autoload_clear_comment()
    {
        $this->clear_comment = strip_tags(htmlspecialchars_decode($this->comment));
        return $this->clear_comment;
    }
}