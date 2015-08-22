<?php

namespace monolyth;

class Comment_Model extends core\Model
{
    use utils\HTML_Helper;
    use User_Access;

    /**
     * By default, allow HTML in comments. Set this to false in your
     * dependencies to disable it.
     */
    public $allowHTML = true;

    const STATUS_DELETED = 1;
    const STATUS_DELETED_PERMANENTLY = 2;
    const STATUS_HIDDEN = 4;

    public function create(core\Post_Form $form)
    {
        $values = ['hash' => ''];
        $values['reference'] = $form['references']->value;
        if (self::user()->loggedIn()) {
            $values['owner'] = self::user()->id();
            $values['name'] = self::user()->name();
        }
        $values['ip'] = $_SERVER['REMOTE_ADDR'];
        $values['status'] = (int)$form['status']->value;
        $form['comment']->value = stripslashes($form['comment']->value);
        
        // Actual comment is special: it could be HTML, and should be valid.
        if ($this->allowHTML) {
            $form['comment']->value = $this->purify(
                $form['comment']->value,
                isset($this->allowed) ? $this->allowed : null
            );
        } else {
            $form['comment']->value = strip_tags($form['comment']->value);
        }
        $values['comment'] = $form['comment']->value;
        if ($form['replyto']->value) {
            $values['replyto'] = $form['replyto']->value;
        }
        $success = 10;
        while ($success) {
            try {
                self::adapter()->insert('monolyth_comment', $values);
                $this->load(self::adapter()->row(
                    'monolyth_comment',
                    '*',
                    ['id' => self::adapter()->lastInsertId(
                        'monolyth_comment_id_seq'
                    )]
                ));
                return null;
            } catch (adapter\sql\InsertNone_Exception $e) {
                return 'insert';
            } catch (adapter\Exception $e) {
                // unique key violation
                sleep(1);
                $success--;
            }
        }
        return 'unknown';
    }

    public function update(core\Post_Form $form)
    {
        $values = [];
        $values['ip'] = $_SERVER['REMOTE_ADDR'];
        $form['comment']->value = stripslashes($form['comment']->value);
        
        // Actual comment is special: it could be HTML, and should be valid.
        if ($this->allowHTML) {
            $form['comment']->value = $this->purify(
                $form['comment']->value,
                isset($this->allowed) ? $this->allowed : null
            );
        } else {
            $form['comment']->value = strip_tags($form['comments']->value);
        }
        $values['comment'] = $form['comment']->value;
        $success = 10;
        while ($success) {
            try {
                self::adapter()->update(
                    'monolyth_comment',
                    $values,
                    $this['id']
                );
                return null;
            } catch (adapter\sql\UpdateNone_Exception $e) {
                return 'update';
            } catch (adapter\Exception $e) {
                // unique key violation
                sleep(1);
                $success--;
            }
        }
        return 'unknown';
    }

    public function delete()
    {
        try {
            self::adapter()->update(
                'monolyth_comment',
                ['status' => [sprintf("status | '%d'", self::STATUS_DELETED)]],
                ['id' => $this['id']]
            );
            return null;
        } catch (adapter\sql\NoResults_Exception $e) {
            return 'owner';
        } catch (adapter\sql\UpdateNone_Exception $e) {
            return 'noneed';
        } catch (adapter\Exception $e) {
            return 'generic';
        }
    }
}

