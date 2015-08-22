<?php

/**
 * Media model for file-based storage.
 */
namespace monolyth;
use monolyth\adapter\sql\InsertNone_Exception;
use monolyth\adapter\sql\UpdateNone_Exception;
use monolyth\adapter\sql\DeleteNone_Exception;
use ErrorException;
use Project;

class File_Media_Model extends core\Model
{
    use User_Access;

    public function __construct()
    {
        parent::__construct();
        $this->media = new render\Media_Helper(Project::instance());
    }

    public function create(array $file, $folder = null, $owner = null)
    {
        if (!isset($owner)) {
            $owner = self::user()->id();
        }
        if (!$owner) {
            return 'owner';
        }
        $md5 = md5(file_get_contents($file['tmp_name']));
        $mime = mime_content_type($file['tmp_name']);
        try {
            self::adapter()->insert(
                'monolyth_media',
                [
                    'filename' => $file['tmp_name'],
                    'originalname' => $file['name'],
                    'md5' => $md5,
                    'filesize' => $file['size'],
                    'owner' => $owner,
                    'mimetype' => $mime,
                    'folder' => $folder,
                ]
            );
            $id = self::adapter()->lastInsertId('monolyth_media_id_seq');
            $parts = str_split($id, 3);
            $name = array_pop($parts);
            $config = Config::get('monolyth');
            if ($parts) {
                $dir = $config->uploadPath.'/'.implode('/', $parts);
            } else {
                $dir = $config->uploadPath;
            }
            try {
                mkdir($dir, 0777, true);
            } catch (ErrorException $e) {
            }
            $ext = substr($mime, strrpos($mime, '/') + 1);
            rename($file['tmp_name'], "$dir/$name.$ext");
            chmod("$dir/$name.$ext", 0644);
            self::adapter()->update(
                'monolyth_media',
                ['filename' => "$dir/$name.$ext"],
                compact('id')
            );
            $this->load(self::adapter()->row(
                'monolyth_media',
                '*',
                compact('id')
            ));
            return null;
        } catch (InsertNone_Exception $e) {
            try {
                self::adapter()->update(
                    'monolyth_media',
                    compact('folder'),
                    compact('md5')
                );
            } catch (UpdateNone_Exception $e) {
            }
            $this->load(self::adapter()->row(
                'monolyth_media',
                '*',
                compact('md5')
            ));
            return null;
        }
    }

    public function move($id, $folder)
    {
        try {
            $owner = self::user()->id();
            self::adapter()->update(
                'monolyth_media',
                compact('folder'),
                compact('id', 'owner')
            );
            return null;
        } catch (UpdateNone_Exception $e) {
            return 'nochange';
        }
    }

    public function delete()
    {
        try {
            self::adapter()->delete('monolyth_media', ['id' => $this['id']]);
        } catch (DeleteNone_Exception $e) {
        }
        try {
            unlink($this['filename']);
        } catch (ErrorException $e) {
        }
        try {
            $parts = explode(DIR_SEPARATOR, dirname($this['filename']));
            // Recursively remove directories when empty.
            while ($parts) {
                $d = Dir(implode(DIR_SEPARATOR, $parts));
                $files = false;
                while (false !== ($entry = $d->read())) {
                    if ($entry != '.' && $entry != '..') {
                        $files = true;
                        break;
                    }
                }
                if (!$files) {
                    rmdir(implode(DIR_SEPARATOR, $parts));
                } else {
                    break;
                }
                array_pop($parts);
            }
        } catch (ErrorException $e) {
        }
    }
}

