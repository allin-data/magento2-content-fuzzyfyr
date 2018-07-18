<?php
/**
 * This file is part of the Content Fuzzyfyr module for Magento2.
 *
 * (c) All.In Data GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AllInData\ContentFuzzyfyr\Test\Unit\Observer;

use AllInData\ContentFuzzyfyr\Handler\CategoryImageHandler;
use AllInData\ContentFuzzyfyr\Model\Configuration;
use AllInData\ContentFuzzyfyr\Observer\CategoryImageObserver;
use AllInData\ContentFuzzyfyr\Test\Unit\AbstractTest;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CategoryImageObserverTest
 * @package AllInData\ContentFuzzyfyr\Test\Unit\Observer
 */
class CategoryImageObserverTest extends AbstractTest
{
    /**
     * @test
     */
    public function stopOnFailedValidationSuccessfully()
    {
        $urlRewriteCollectionFactory = $this->getUrlRewriteCollectionFactory();
        $urlRewriteCollectionFactory->expects($this->never())
            ->method('create');

        $categoryResourceFactory = $this->getCategoryResourceFactory();
        $categoryResourceFactory->expects($this->never())
            ->method('create');

        $categoryCollectionFactory = $this->getCategoryCollectionFactory();
        $categoryCollectionFactory->expects($this->never())
            ->method('create');

        $configuration = $this->getConfiguration();
        $configuration->expects($this->once())
            ->method('isApplyToCategories')
            ->willReturn(false);

        $eventObserver = $this->getObserver();
        $eventObserver->expects($this->once())
            ->method('getData')
            ->with('configuration')
            ->willReturn($configuration);

        $categoryImageHandler = $this->getCategoryImageHandler();
        $categoryImageHandler->expects($this->never())
            ->method('getMediaCopyOfFile');

        $observer = new CategoryImageObserver($categoryCollectionFactory, $categoryResourceFactory, $urlRewriteCollectionFactory, $categoryImageHandler);

        $observer->execute($eventObserver);
    }

    /**
     * @test
     */
    public function runSuccessfully()
    {
        $expectedImagePath = 'foo/bar/baz.png';

        $urlRewrite = $this->getMockBuilder(\Magento\UrlRewrite\Model\UrlRewrite::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRewrite->expects($this->once())
            ->method('delete');

        $urlRewriteCollection = $this->getMockBuilder(UrlRewriteCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRewriteCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_type', ['eq' => 'category'])
            ->willReturnSelf();
        $urlRewriteCollection->expects($this->once())
            ->method('load');
        $urlRewriteCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$urlRewrite]);

        $urlRewriteCollectionFactory = $this->getUrlRewriteCollectionFactory($urlRewriteCollection);

        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $idx = 0;
        $category->expects($this->at($idx++))
            ->method('getId')
            ->willReturn(42);
        $category->expects($this->at($idx++))
            ->method('getId')
            ->willReturn(42);
        $category->expects($this->at($idx++))
            ->method('load')
            ->with(42);
        $category->expects($this->at($idx++))
            ->method('getImageUrl')
            ->willReturn(' ');
        $category->expects($this->at($idx++))
            ->method('setData')
            ->With('image', basename($expectedImagePath));

        $categoryResource = $this->getMockBuilder(CategoryResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryResource->expects($this->once())
            ->method('save')
            ->with($category);
        $categoryResourceFactory = $this->getCategoryResourceFactory($categoryResource);

        $categoryCollection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryCollection->expects($this->once())
            ->method('load');
        $categoryCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$category]);
        $categoryCollectionFactory = $this->getCategoryCollectionFactory($categoryCollection);

        $configuration = $this->getConfiguration();
        $configuration->expects($this->once())
            ->method('isApplyToCategories')
            ->willReturn(true);
        $configuration->expects($this->any())
            ->method('isUseOnlyEmpty')
            ->willReturn(true);
        $configuration->expects($this->any())
            ->method('getDummyImagePath')
            ->willReturn($expectedImagePath);

        $eventObserver = $this->getObserver();
        $eventObserver->expects($this->once())
            ->method('getData')
            ->with('configuration')
            ->willReturn($configuration);

        $categoryImageHandler = $this->getCategoryImageHandler();
        $categoryImageHandler->expects($this->once())
            ->method('getMediaCopyOfFile')
            ->willReturnArgument(0);


        $observer = new CategoryImageObserver($categoryCollectionFactory, $categoryResourceFactory, $urlRewriteCollectionFactory, $categoryImageHandler);

        $observer->execute($eventObserver);
    }

    /**
     * @test
     */
    public function runSuccessfullyWithOnlyEmptyFlagAndNonEmptyImageUrl()
    {
        $expectedImagePath = 'foo/bar/baz.png';

        $urlRewrite = $this->getMockBuilder(\Magento\UrlRewrite\Model\UrlRewrite::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRewrite->expects($this->once())
            ->method('delete');

        $urlRewriteCollection = $this->getMockBuilder(UrlRewriteCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRewriteCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_type', ['eq' => 'category'])
            ->willReturnSelf();
        $urlRewriteCollection->expects($this->once())
            ->method('load');
        $urlRewriteCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$urlRewrite]);

        $urlRewriteCollectionFactory = $this->getUrlRewriteCollectionFactory($urlRewriteCollection);

        $rootCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rootCategory->expects($this->once())
            ->method('getId')
            ->willReturn(CategoryImageObserver::ROOT_CATEGORY_ID);

        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $idx = 0;
        $category->expects($this->at($idx++))
            ->method('getId')
            ->willReturn(42);
        $category->expects($this->at($idx++))
            ->method('getId')
            ->willReturn(42);
        $category->expects($this->at($idx++))
            ->method('load')
            ->with(42);
        $category->expects($this->at($idx++))
            ->method('getImageUrl')
            ->willReturn(basename($expectedImagePath));

        $categoryResource = $this->getMockBuilder(CategoryResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryResource->expects($this->once())
            ->method('save')
            ->with($category);
        $categoryResourceFactory = $this->getCategoryResourceFactory($categoryResource);

        $categoryCollection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryCollection->expects($this->once())
            ->method('load');
        $categoryCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$rootCategory, $category]);
        $categoryCollectionFactory = $this->getCategoryCollectionFactory($categoryCollection);

        $configuration = $this->getConfiguration();
        $configuration->expects($this->once())
            ->method('isApplyToCategories')
            ->willReturn(true);
        $configuration->expects($this->any())
            ->method('isUseOnlyEmpty')
            ->willReturn(true);
        $configuration->expects($this->any())
            ->method('getDummyImagePath')
            ->willReturn($expectedImagePath);

        $eventObserver = $this->getObserver();
        $eventObserver->expects($this->once())
            ->method('getData')
            ->with('configuration')
            ->willReturn($configuration);

        $categoryImageHandler = $this->getCategoryImageHandler();
        $categoryImageHandler->expects($this->never())
            ->method('getMediaCopyOfFile');

        $observer = new CategoryImageObserver($categoryCollectionFactory, $categoryResourceFactory, $urlRewriteCollectionFactory, $categoryImageHandler);

        $observer->execute($eventObserver);
    }

    /**
     * @return MockObject|Observer
     */
    private function getObserver()
    {
        return $this->createMock(Observer::class);
    }

    /**
     * @return MockObject|Configuration
     */
    private function getConfiguration()
    {
        return $this->createMock(Configuration::class);
    }

    /**
     * @param MockObject $instance
     * @return MockObject|\Magento\Catalog\Model\ResourceModel\CategoryFactory
     */
    private function getCategoryResourceFactory(MockObject $instance = null)
    {
        return $this->getFactoryAsMock('\Magento\Catalog\Model\ResourceModel\Category', $instance);
    }

    /**
     * @param MockObject $instance
     * @return MockObject|\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private function getCategoryCollectionFactory(MockObject $instance = null)
    {
        return $this->getFactoryAsMock('\Magento\Catalog\Model\ResourceModel\Category\Collection', $instance);
    }

    /**
     * @return MockObject|CategoryImageHandler
     */
    private function getCategoryImageHandler()
    {
        return $this->getMockBuilder(CategoryImageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param MockObject $instance
     * @return MockObject|\Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
     */
    private function getUrlRewriteCollectionFactory(MockObject $instance = null)
    {
        return $this->getFactoryAsMock('\Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection', $instance);
    }
}