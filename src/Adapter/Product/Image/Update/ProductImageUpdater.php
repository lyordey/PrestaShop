<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Product\Image\Update;

use Image;
use PrestaShop\PrestaShop\Adapter\Product\Image\Repository\ProductImageRepository;
use PrestaShop\PrestaShop\Adapter\Product\Image\Uploader\ProductImageUploader;
use PrestaShop\PrestaShop\Core\Domain\Product\Image\Exception\CannotDeleteProductImageException;
use PrestaShop\PrestaShop\Core\Domain\Product\Image\Exception\CannotUpdateProductImageException;
use PrestaShop\PrestaShop\Core\Domain\Product\Image\ValueObject\ImageId;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;
use PrestaShop\PrestaShop\Core\Image\Exception\CannotUnlinkImageException;

class ProductImageUpdater
{
    /**
     * @var ProductImageUploader
     */
    private $productImageUploader;

    /**
     * @var ProductImageRepository
     */
    private $productImageRepository;

    /**
     * @param ProductImageRepository $productImageRepository
     * @param ProductImageUploader $productImageUploader
     */
    public function __construct(
        ProductImageRepository $productImageRepository,
        ProductImageUploader $productImageUploader
    ) {
        $this->productImageRepository = $productImageRepository;
        $this->productImageUploader = $productImageUploader;
    }

    /**
     * @param ImageId $imageId
     *
     * @throws CannotDeleteProductImageException
     * @throws CannotUnlinkImageException
     */
    public function deleteImage(ImageId $imageId)
    {
        $image = $this->productImageRepository->get($imageId);

        $this->productImageUploader->remove($image);
        $this->productImageRepository->delete($image);

        if ($image->cover) {
            $images = $this->productImageRepository->getImages(new ProductId((int) $image->id_product));
            if (count($images) > 0) {
                $firstImage = $images[0];
                $this->updateCover($firstImage, true);
            }
        }
    }

    /**
     * @param ImageId $imageId
     *
     * @throws CannotUpdateProductImageException
     */
    public function updateProductCover(ImageId $imageId): void
    {
        $newCover = $this->productImageRepository->get($imageId);
        $productId = new ProductId((int) $newCover->id_product);
        $currentCover = $this->productImageRepository->findCover($productId);

        if ($currentCover) {
            $this->updateCover($currentCover, false);
        }

        $this->updateCover($newCover, true);
    }

    /**
     * @param Image $image
     * @param bool $isCover
     *
     * @throws CannotUpdateProductImageException
     */
    private function updateCover(Image $image, bool $isCover): void
    {
        $image->cover = $isCover;
        $this->productImageRepository->partialUpdate(
            $image,
            ['cover'],
            CannotUpdateProductImageException::FAILED_UPDATE_COVER
        );
    }
}
