<?php

namespace App\Models\Media;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;

trait MediaModelAvatarTrait
{
	public function getAvatarUrl()
    {
        return $this->getFirstMediaUrl('avatar');
    }

    public function getAvatarThumbnailUrl()
    {
        return $this->getFirstThumbnailUrl('avatar');
    }

    public function getAvatarSmallThumbnail()
    {
        return $this->getFirstSmallThumbnailUrl('avatar');
    }

    public function removeCurrentAvatar()
    {
        return $this->removeMedias('avatar');
    }

    public function uploadNewAvatar($fileUpload, $filename = null)
    {
        return $this->addMediaFromFileUpload($fileUpload, 'avatar', $filename);
    }
}