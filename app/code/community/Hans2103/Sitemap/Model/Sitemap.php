<?php
/**
 * Hans2103
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Hans2103
 * @package     Hans2103_Sitemap
 * @copyright   Copyright (c) 2012 Hans2103 Internet. (http://www.Hans2103.nl)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Sitemap model
 *
 * @category   Hans2103
 * @package    Hans2103_Sitemap
 * @author     Magento Core Team <core@magentocommerce.com>
 * @editor     Hans2103 <support@Hans2103.nl>
 */
class Hans2103_Sitemap_Model_Sitemap extends Mage_Sitemap_Model_Sitemap
{
    /**
     * Generate XML file
     *
     * @return Mage_Sitemap_Model_Sitemap
     */
    public function generateXml()
    {
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $this->getPath()));

        if ($io->fileExists($this->getSitemapFilename()) && !$io->isWriteable($this->getSitemapFilename())) {
            Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }

        $io->streamOpen($this->getSitemapFilename());

        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:content="http://www.google.com/schemas/sitemap-content/1.0" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n");

        $storeId = $this->getStoreId();
        $date    = Mage::getSingleton('core/date')->gmtDate('Y-m-d');
        $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

        /**
         * Generate categories sitemap
         */
        $changefreq = (string)Mage::getStoreConfig('sitemap/category/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/category/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/catalog_category')->getCollection($storeId);
        foreach ($collection as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>' . "\n",
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            $io->streamWrite($xml);
        }
        unset($collection);

        /**
         * Generate products sitemap
         */
        /**
         * Hans2103 override to include images in sitemap
         */
	    $changefreq = (string) Mage::getStoreConfig('sitemap/product/changefreq', $storeId);
	    $priority   = (string) Mage::getStoreConfig('sitemap/product/priority', $storeId);
	    $collection = Mage::getResourceModel('sitemap/catalog_product')->getCollection($storeId);
	    foreach ($collection as $item)
	    {


		    $xml = '<url><loc>' . htmlspecialchars($baseUrl . $item->getUrl()) . '</loc>';

		    $image      = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getId(), 'image', $storeId);
		    $imageLoc   = '';
		    $imageTitle = '';

		    if ($image)
		    {
			    $imageLoc   = str_replace('index.php/', '', Mage::getURL('media/catalog/product') . substr($image, 1));
			    $imageTitle = htmlspecialchars(Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getId(), 'name', $storeId));
			    $imageTitle = strip_tags(preg_replace("/&(?!#?[a-z0-9]+;)/", "&amp;",$imageTitle));
			    $xml        .= '<image:image><image:loc>' . $imageLoc . '</image:loc><image:title>' . $imageTitle . '</image:title></image:image>';
		    }

		    $product = Mage::getModel('catalog/product')->load($item->getId());
		    $_images = $product->getMediaGalleryImages();
		    foreach ($_images as $image):
			    if ($image->getUrl() == $imageLoc) continue;
			    $xml .= '<image:image><image:loc>' . $image->getUrl() . '</image:loc></image:image>';
		    endforeach;
		    unset($product);
		    $xml .= '<lastmod>' . $date . '</lastmod><changefreq>' . $changefreq . '</changefreq><priority>' . $priority . '</priority></url>' . "\n";

		    $io->streamWrite($xml);
	    }
	    unset($collection);
        /**
         * Generate cms pages sitemap
         */
        $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/page/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/cms_page')->getCollection($storeId);
        foreach ($collection as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>' . "\n",
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            $io->streamWrite($xml);
        }
        unset($collection);

        $io->streamWrite('</urlset>');
        $io->streamClose();

        $this->setSitemapTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }
}
