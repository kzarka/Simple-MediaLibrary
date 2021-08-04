<?php

namespace App\Models\Media;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;

trait MediaModelTrait
{
    public function getConversions($type, $property = 'path')
    {
        return config('library.conversion.'. $type . '.' . $property);
    }

    public function getFirstMedia($collection = null)
    {
        return $this->queryMedias($collection)->first();
    }

    /**
     * get first media path
     * @param null $collection
     * @return mixed
     */
    public function getFirstMediaPath($collection = null, $conversion = 'default')
    {
        return $this->queryMedias($collection, $conversion)->pluck('file_path')->first();
    }

    /**
     * get media uniq paths
     * @param null $collection
     * @return mixed
     */
    public function getMediaUniqPaths($collection = null, $conversion = 'default')
    {
        return $this->queryMedias($collection, $conversion)->pluck('uniq_path');
    }

    /**
     * get media paths
     * @param null $collection
     * @return mixed
     */

    public function getMediaPaths($collection = null, $conversion = 'default')
    {
        return $this->queryMedias($collection, $conversion)->pluck('file_path');
    }

    /**
     * get media urls
     * @param null $collection
     * @return mixed
     */
    public function getMediaItems($collection = null, $conversion = 'default')
    {
        return $this->queryMedias($collection, $conversion)->get()->map(function ($item) {
            $item->file_path = $this->getUrlByPath($item->file_path);
            return $item;
        });
    }

    /**
     * get media urls
     * @param null $collection
     * @return mixed
     */
    public function getMediaUrls($collection = null, $conversion = 'default')
    {
        return $this->getMediaPaths($collection, $conversion)->map(function ($filePath) {
            return $this->getUrlByPath($filePath);
        });
    }

    /**
     * get first media from list medias urls
     * @param null $collection
     * @return mixed|null
     */
    public function getFirstMediaUrl($collection = null, $conversion = 'default')
    {
        if ($filePath = $this->getFirstMediaPath($collection, $conversion)) {
            return $this->getUrlByPath($filePath);
        }
        return isset(self::$mediaConfigs['fall_back']) ? self::$mediaConfigs['fall_back'] : null;
    }

    /**
     * get first media from list medias urls
     * @param null $collection
     * @return mixed|null
     */
    public function getFirstThumbnailUrl($collection = null)
    {
        return $this->getFirstMediaUrl($collection, $this->getConversions('thumbnail'));
    }

    /**
     * get first media from list medias urls
     * @param null $collection
     * @return mixed|null
     */
    public function getFirstSmallThumbnailUrl($collection = null)
    {
        return $this->getFirstMediaUrl($collection, $conversion = $this->getConversions('small_thumbnail'));
    }

    /**
     ** get first media that fixed by some url
     * @param null $collection
     * @return mixed
     */
    public function getFirstFixedMediaUrl($collection = null)
    {
        return $this->queryMedias($collection)->pluck('fixed_url')->first();
    }

    /**
     * get media that fixed by some url
     * @param null $collection
     * @return mixed
     */
    public function getFixedMediaUrls($collection = null)
    {
        return $this->queryMedias($collection)->pluck('fixed_url');
    }

    /**
     * add media from path
     * @param $path
     * @param null $collection
     * @param null $filename
     */
    public function addMediaFromPath($path, $collection = null, $filename = null)
    {
        $fileToSave = \Image::make($path);
        $this->addMediaFromImage($fileToSave, $collection, $filename);
    }

    /**
     * add media file from url
     * @param $url
     * @param null $collection
     * @param null $filename
     */
    public function addMediaFromUrl($url, $collection = null, $filename = null)
    {
        $fileToSave = \Image::make($url);
        $this->addMediaFromImage($fileToSave, $collection, $filename);
    }

    /**
     * add media file from upload file
     * @param null $fileUpload
     * @param null $collection
     * @param null $filename
     */
    public function addMediaFromFileUpload($fileUpload, $collection = null, $filename = null)
    {
        $fileToSave = \Image::make($fileUpload);
        $this->addMediaFromImage($fileToSave, $collection, $filename);
    }

    /**
     * add media from base64
     * @param $base64
     * @param null $collection
     * @param null $filename
     */
    public function addMediaFromBase64($base64, $collection = null, $filename = null)
    {
        $fileToSave = \Image::make($base64);
        $this->addMediaFromImage($fileToSave, $collection, $filename);
    }

    /**
     * add media file from file object (File)
     * @param Image $fileToSave
     * @param null $collection
     * @param null $filename
     */
    public function addMediaFromImage(Image $fileToSave, $collection = null, $filename = null)
    {
        try {
            $uniqPath = uniqid();
            if (is_null($filename)) {
                $filenameHash = hash('tiger192,3', uniqid());
                $filenameExt = $fileToSave->mime() ? explode('/', $fileToSave->mime())[1] : null;
                $filename = $filenameHash . (empty($filenameExt) ? null : '.' . $filenameExt);
            }
            $this->addMediaCollectionFromImage($fileToSave, $collection, 'default', $filename, $uniqPath);
            if (isset(self::$mediaConfigs['conversions'][$collection])) {
                $conversions = self::$mediaConfigs['conversions'][$collection];
                if (is_array($conversions)) {
                    foreach ($conversions as $type) {
                        $width = $this->getConversions($type, 'width');
                        $height = $this->getConversions($type, 'height');
                        if ($width && $height) {
                            $newFileToSave = (clone $fileToSave);
                            $newFileToSave->resize($width, $height);
                        } else {
                            $newFileToSave = (clone $fileToSave);
                            $newFileToSave->resize($width, $height, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                        }
                        $conversionPath = $this->getConversions($type, 'path');
                        $this->addMediaCollectionFromImage($newFileToSave, $collection, $conversionPath, $filename, $uniqPath);
                    }
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            echo $e->getMessage();
            \DB::rollback();
        }
    }

    public function addMediaCollectionFromImage(Image $fileToSave, $collection = null, $conversion, $filename = null, $uniqPath)
    {
        $mediaTable = self::$mediaConfigs['table'];
        $foreignKey = self::$mediaConfigs['foreign_key'];
        $pathPrefix = self::$mediaConfigs['path_prefix'];

        if(empty($uniqPath)) {
            $uniqPath = uniqid();
        }
        
        if (is_null($filename)) {
            $filenameHash = hash('tiger192,3', uniqid());
            $filenameExt = $fileToSave->mime() ? explode('/', $fileToSave->mime())[1] : null;
            $filename = $filenameHash . (empty($filenameExt) ? null : '.' . $filenameExt);
        }
        $pathToStore = "$pathPrefix/$collection/$conversion/$uniqPath";
        try {
            \DB::beginTransaction();
            $id = \DB::table($mediaTable)->insertGetId([
                $foreignKey => $this->id,
                'model'     => $this->table,
                'uniq_path' => $uniqPath,
                'collection' => $collection,
                'conversion' => $conversion,
                'name' => $filename,
                'created_at' => Carbon::now()
            ]);
            Storage::disk(config('library.disk_name'))->put($pathToStore . '/' . $filename, $fileToSave->encode()->__toString());
            \DB::commit();

            return $id;
        } catch (\Exception $e) {
            dd($e->getMessage());
            \Log::info($e->getMessage());
            \DB::rollback();
        }
    }

    /**
     * add media url into model
     * @param $url
     * @param null $collection
     * @return
     */
    public function addFixedMediaUrl($url, $collection = null)
    {
        return \DB::table(self::$mediaConfigs['table'])->insert([
            self::$mediaConfigs['foreign_key'] => $this->id,
            'model' => $this->table,
            'collection' => $collection,
            'fixed_url' => $url,
            'uniq_path' => uniqid(),
            'created_at' => Carbon::now()
        ]);
    }

    public function removeMedias($collection = null)
    {
        $rm = \DB::table(self::$mediaConfigs['table'])->where([
            self::$mediaConfigs['foreign_key'] => $this->id,
            'model'                            => $this->table
        ]);
        if ($collection) {
            $rm = $rm->where(['collection' => $collection]);
        }
        /**  Delete file in local */
        foreach ($rm->get() as $item) {
            try {
                $this->deleteDirectory($item->uniq_path, $item->collection, $item->conversion);
            } catch (\Exception $e) {
                \Log::info($e->getMessage());
            }
        }

        $rm->delete();
    }

    /**
     * remove media by unique path
     * @param $uniq_path
     * @return mixed
     */
    public function removeMediaByUniqPath($uniq_path)
    {
        $rm = \DB::table(self::$mediaConfigs['table'])->where([
            self::$mediaConfigs['foreign_key'] => $this->id,
            'model'=> $this->table,
            'uniq_path' => $uniq_path
        ]);

        /**  Delete file in local */
        foreach ($rm->get() as $item) {
            try {
                $this->deleteDirectory($item->uniq_path, $item->collection, $item->conversion);
            } catch (\Exception $e) {
                \Log::info($e->getMessage());
            }
        }

        $rm->delete();
    }

    /**
     * get url image by file path
     * @param $filePath
     * @return mixed
     */
    public function getUrlByPath($filePath)
    {
        return \Storage::disk(config('library.disk_name'))->url(self::$mediaConfigs['path_prefix'] . '/' . $filePath);
    }

    /**
     * raw query medias
     * @param null $collection
     * @return mixed
     */
    public function queryMedias($collection = null, $conversion)
    {
        $mediaTable = self::$mediaConfigs['table'];
        $ownerKey = self::$mediaConfigs['owner_key'];
        $foreignKey = self::$mediaConfigs['foreign_key'];
        $modelTable = $this->table;
        $builder = \DB::table("$mediaTable")->select(\DB::raw("CONCAT(if($mediaTable.collection is null,'',$mediaTable.collection), '/', " . "'" . $conversion ."', '/', $mediaTable.uniq_path, '/', $mediaTable.name) AS file_path"), 'fixed_url', 'collection', 'created_at', 'name', 'id', 'uniq_path')
        ->where($foreignKey, $this->id)
        ->where('model', $modelTable);
        if ($collection) {
            $builder = $builder->where("$mediaTable.collection", $collection);
            $builder = $builder->where("$mediaTable.conversion", $conversion);
        }
        return $builder;
    }


    /**
     * Delete file in storage local
     * @param $filePath
     * @return mixed
     */
    public function deleteFile($filePath)
    {
        return \Storage::disk(config('library.disk_name'))->delete(self::$mediaConfigs['path_prefix'] . '/' . $filePath);
    }

    /**
     * Delete directory in storage local
     * @param $uniq_path
     * @return mixed
     */
    public function deleteDirectory($uniq_path, $collection, $conversion)
    {
        try{
            return \Storage::disk(config('library.disk_name'))->deleteDirectory(self::$mediaConfigs['path_prefix'] . '/' . $collection . '/'. $conversion . '/'. $uniq_path);
        } catch(\Exception $e) {
            \Log::info($e->getMessage());
        }
        
    }
}
