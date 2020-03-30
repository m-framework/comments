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

class edit extends module {

    public function _init()
    {
        if (!isset($this->view->{'comment_' . $this->name . '_form'})) {
            return false;
        }

        $comment = new comments(!empty($this->get->edit) ? $this->get->edit : null);

        if (!empty($comment->id)) {
            view::set('page_title', '<h1><i class="fa fa-file-text-o"></i> ' . i18n::get('To edit comment') . ' `' .
                $comment->id . '`</h1>');
            registry::set('title', i18n::get('To edit comment'));
        }

        if (empty($comment->site)) {
            $comment->site = $this->site->id;
        }
        if (empty($comment->author)) {
            $comment->author = $this->user->profile;
        }

        $pages_tree = $this->page->get_pages_tree();

        if (empty($pages_tree)) {
            $this->page->prepare_page([]);
            $pages_tree = $this->page->get_pages_tree();
        }

        $pages_arr = empty($pages_tree) ? [] : pages::options_arr_recursively($pages_tree, '');



        new form(
            $comment,
            [
                'page' => [
                    'field_name' => i18n::get('Page'),
                    'related' => $pages_arr,
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