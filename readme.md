# Project Modules Overview

This document provides a high-level architectural overview of the custom modules within this Magento 2 project. It is designed to serve as a technical blueprint, explaining the purpose, components, and data flow of each module.

The modules are explained in a hierarchical structure, detailing the function of each key file.

---

## 1. Bosta Shipping Integration (Module: `Elsherif_Bosta`)

### Purpose
This module integrates Bosta as a custom shipping carrier. Its primary functions are to fetch real-time shipping rates from the Bosta API during checkout, allow merchants to create shipments in the Bosta system directly from the Magento admin, and provide tracking information to customers.

### Hierarchy & Components

- **`app/code/Elsherif/Bosta/`**
    - **`etc/module.xml`**: Registers the `Elsherif_Bosta` module.
    - **`etc/config.xml`**: Defines default values for the shipping method's configuration, such as the default API endpoint, shipping method name, and allowed countries.
    - **`etc/adminhtml/system.xml`**: Creates the configuration fields in the Magento admin under `Stores > Configuration > Sales > Shipping Methods`. This is where the merchant will enable the method, enter their Bosta API Key, and set other preferences.
    - **`etc/di.xml`**: Used for dependency injection. For example, it can be used to inject a custom Logger for debugging API calls or to substitute a core class with a custom implementation if needed.
    - **`Model/`**
        - **`Carrier/Bosta.php`**: The core class for the shipping method. It must implement `Magento\Shipping\Model\Carrier\CarrierInterface`.
            - **`collectRates(RateRequest $request)`**: This is the most important method. Magento calls it during checkout. Its job is to:
                1.  Receive the cart details (destination, weight, value).
                2.  Make an API call to Bosta with this information.
                3.  Parse the API response, which should contain shipping service names and prices.
                4.  Format the response into Magento's rate result object and return it to be displayed to the customer.
            - **`proccessShipmentRequest(TrackRequest $request)`**: Called when a merchant creates a shipment for an order. This method sends the shipment details to the Bosta API to formally create the delivery and retrieve a tracking number.
        - **`Bosta/ApiClient.php`** (Recommended Best Practice): A dedicated helper class responsible for all communication with the Bosta API. It handles authentication, formats requests, and parses responses. This separates the raw API logic from the Magento carrier logic in `Bosta.php`, making the code cleaner and easier to test.

---

## 2. Custom Product (Module: `Elsherif_Product`)

### Purpose
This module introduces a new product type called `custom_configurable`. It extends Magento's standard configurable product, providing a unique architecture for managing product variations and their presentation on the storefront.

### Hierarchy & Components

- **`app/code/Elsherif/Product/`**
    - **`etc/module.xml`**: Registers the `Elsherif_Product` module.
    - **`etc/product_types.xml`**: The declarative file that officially defines the `custom_configurable` product type for Magento, linking it to its core model.
    - **`Model/`**
        - **`Product/Type/CustomConfigurable.php`**: The backend engine for the product type. It defines how the product saves, how it handles its associated simple products, and its general behavior. It will likely extend `Magento\ConfigurableProduct\Model\Product\Type\Configurable`.
        - **`Product/Price.php`**: A custom price model class that defines how the product's price is calculated and displayed. For a configurable product, this usually involves logic to determine the price based on the selected variation.
    - **`view/`**
        - **`adminhtml/ui_component/product_form.xml`**: Modifies the admin product edit page. It adds the custom user interface (a new fieldset or section) where merchants can create, assign, and manage the simple product variations for the `custom_configurable` parent product.
        - **`frontend/layout/catalog_product_view_type_custom_configurable.xml`**: A specific layout file that dictates which blocks and templates to render on the product detail page when a customer is viewing a `custom_configurable` product.
        - **`frontend/templates/product/view/type/custom_configurable.phtml`**: The template file that renders the actual HTML for the product options (e.g., color swatches, size dropdowns) on the storefront. It will work with JavaScript to update the page dynamically.

---

## 3. News Module (Module: `Elsherif_News`)

### Purpose
This module creates a simple Content Management System (CMS) for news articles. It allows merchants to create, edit, and manage news articles in the admin panel and display them on the frontend in a list and on individual detail pages.

### Hierarchy & Components

- **`app/code/Elsherif/News/`**
    - **`etc/module.xml`**: Registers the `Elsherif_News` module.
    - **`etc/db_schema.xml`**: Defines the schema for the custom database table (e.g., `elsherif_news_article`) that will store the news articles. Columns would include `article_id`, `title`, `content`, `author`, `image_path`, `created_at`, etc. Running `bin/magento setup:upgrade` will create this table.
    - **`etc/adminhtml/`**
        - **`menu.xml`**: Adds a new menu item in the admin sidebar (e.g., under `Content > Elements > News Articles`) to provide easy access to the news management grid.
        - **`routes.xml`**: Defines the admin URL for the news management section (e.g., `admin/news/article`).
    - **`Api/`** (Recommended Best Practice)
        - **`Data/ArticleInterface.php`**: Defines the data structure of a news article using getter and setter methods.
        - **`ArticleRepositoryInterface.php`**: Defines the service contract for managing news articles. It includes methods like `save(ArticleInterface $article)`, `getById($articleId)`, `getList()`, and `deleteById($articleId)`. Using a repository pattern is a Magento best practice.
    - **`Model/`**
        - **`Article.php`**: The model class that implements `ArticleInterface`.
        - **`ArticleRepository.php`**: The repository implementation that contains the actual logic for saving, loading, and deleting articles from the database.
        - **`ResourceModel/Article.php`** and **`ResourceModel/Article/Collection.php`**: The resource model and collection classes that provide the direct database abstraction for the `elsherif_news_article` table.
    - **`Controller/`**
        - **`Adminhtml/Article/Index.php`**: The controller for displaying the admin grid of news articles.
        - **`Adminhtml/Article/Edit.php`**: The controller for the admin form to create or edit an article.
        - **`Index/Index.php`**: A frontend controller to display a list of all news articles (e.g., at the URL `yourstore.com/news`).
        - **`View/Index.php`**: A frontend controller to display a single news article (e.g., at `yourstore.com/news/view/id/1`).
    - **`view/`**
        - **`adminhtml/ui_component/`**: Contains the UI component XML files (`news_article_listing.xml`, `news_article_form.xml`) that define the admin grid and form.
        - **`frontend/layout/`** and **`frontend/templates/`**: The layout XML and PHTML template files for rendering the news list and detail pages on the storefront.
