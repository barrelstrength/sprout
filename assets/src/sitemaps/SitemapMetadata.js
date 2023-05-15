import './sitemap-metadata.css';

import Alpine from 'alpinejs';
import {SitemapMetadataRow} from './SitemapMetadataRow';

window.Alpine = Alpine;

Alpine.data('SitemapMetadataRow', SitemapMetadataRow);

Alpine.start();
