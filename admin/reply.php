<?php

namespace modules\comments\admin;

use m\module;
use m\i18n;
use m\registry;
use m\view;
use m\config;
use m\model;
use m\form;
use modules\comments\models\comments;
use modules\pages\models\pages;
use modules\users\models\users;
use modules\users\models\users_info;

class reply extends module {

    public function _init()
    {
        if (!isset($this->view->{'comment_' . $this->name . '_form'}) || empty($this->get->reply)) {
            return false;
        }

        $reply = new comments($this->get->reply);
        $comment = new comments();

        $comment->reply = $reply->id;
        $comment->page = $reply->page;
        $comment->related_model = $reply->related_model;
        $comment->related_id = $reply->related_id;
        $comment->site = $this->site->id;
        $comment->author = $this->user->profile;

        if (!empty($reply->id)) {
            view::set('page_title', '<h1><i class="fa fa-comments-o"></i> ' . i18n::get('A reply to comment') . ' `' .
                $reply->id . '`</h1>');
            registry::set('title', i18n::get('A reply to comment'));

            registry::set('breadcrumbs', [
                '/' . $this->conf->admin_panel_alias . '/comments' => '*Comments*',
                '' => i18n::get('A reply to comment'),
            ]);
        }

        new form(
            $comment,
            [
                'page' => [
                    'field_name' => i18n::get('Page'),
                    'related' => pages::call_static()->s(['id as value', 'name'],[],10000)->all(),
                    'required' => true,
                ],
                'related_model' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Related model'),
                    'required' => true,
                ],
                'related_id' => [
                    'type' => 'int',
                    'field_name' => i18n::get('Related id'),
                    'required' => true,
                ],
                'reply' => [
                    'type' => 'int',
                    'field_name' => i18n::get('Reply id'),
                ],
                'author' => [
                    'field_name' => i18n::get('Author'),
                    'related' => users_info::call_static()->s(['profile as value', "CONCAT(first_name,' ',last_name) as name"],[],10000)->all(),
                ],
                'comment' => [
                    'type' => 'text',
                    'field_name' => i18n::get('Comment'),
                    'required' => true,
                ],
            ],
            [
                'form' => $this->view->{'comment_' . $this->name . '_form'},
                'int' => $this->view->edit_row_int,
                'varchar' => $this->view->edit_row_varchar,
                'text' => $this->view->edit_row_text,
                'related' => $this->view->edit_row_related,
                'saved' => $this->view->edit_row_saved,
                'error' => $this->view->edit_row_error,
            ]
        );
    }
}