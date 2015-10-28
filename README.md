# Wholesale prices (Оптовые цены)
Webasyst Shop-Script plugin
<p>
    <img src="http://docs.itfrogs.ru/wa-data/public/site/img/opt-eng.png" alt="Logo" />
</p>
<p>
    The plugin extends the ability to edit the item, allowing you to create a price for each category of users.
</p>

# Documentation
<p>
    <a href="http://docs.itfrogs.ru/webasyst/plugins/shopscript/opt/" target="_blank">Russian Documentation</a>
</p>

# Installing a plugin
<p>
    Extract the contents of the archive containing plugin's files into any empty folder on your computer and upload the extracted contents to wa-apps/shop/plugins/tocheckout directory on the web server. Replace app_id by the id of the app for which the plugin was developed. Once uploading is completed, a new subdirectory will appear inside wa-apps/shop/plugins/, containing plugin's files and named by the plugin id (tocheckout).
</p>
<p>
    Add plugin id to configuration file wa-config/apps/shop/plugins.php in the form of a line shown below:
</p>
<p>
    'opt' => true,
</p>
<p>
    Clear cache in Installer.
</p>

# How it works
<p>
    Set up plugin settings:
</p>
<p>
    <img src="http://docs.itfrogs.ru/wa-data/public/site/img/plugin-settings-3.png">
</p>
<p>
    Go to the Edit Product section and you will see that you can now edit the prices for each of the selected categories of users.
</p>
<p>
    <img src="http://docs.itfrogs.ru/wa-data/public/site/img/edit-product.png">
</p>

