<?php
namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Avatar
{
    #[Assert\NotBlank(message:"Upload a file")]
    #[Assert\Image(mimeTypes:['image/png','image/jpeg', 'image/jpg', 'image/gif'], mimeTypesMessage:"Upload a jpg, jpeg, png or gif file")]
    #[Assert\File(maxSize:"1024k", maxSizeMessage: "This file is too large to be uploaded.")]
    private $newPicture;

    public function getNewAvatar(): ?string
    {
        return $this->newPicture;
    }

    public function setNewAvatar(?string $newPicture): self
    {
        $this->newPicture = $newPicture;

        return $this;
    }
}