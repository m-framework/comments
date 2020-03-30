<?php

namespace modules\comments\admin;

use m\module;
use m\view;
use m\i18n;
use m\config;
use modules\admin\admin\overview_data;
use modules\pages\models\pages;

class overview extends module {

    public function _init()
    {
        config::set('per_page', 1000);

        $cond = [
            'path' => i18n::get('Path'),
            'title' => i18n::get('Title'),
            'published' => i18n::get('Published'),
            'date' => i18n::get('Date'),
        ];

        view::set('content', overview_data::items(
            'modules\comments\models\comments',
            $cond,
            ['site' => $this->site->id],
            $this->view->overview,
            $this->view->overview_item
        ));
    }
}
