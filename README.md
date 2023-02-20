# Sprout

A collection of modules used by the Sprout Plugins for Craft CMS.

## Issues and Pull Requests

Report issues in the repository for the plugin where the issue was encountered.
To submit a pull request, first create
an issue describing the problem in the appropriate plugin repository and link to
the issue in the PR.

| Plugin Repository  | Issues                             |
|:-------------------|:-----------------------------------|
| Sprout Data Studio | [Data Studio issues][#DataStudioI] |
| Sprout Forms       | [Form issues][#FormsI]             |
| Sprout SEO         | [SEO issues][#SeoI]                |
| Sprout Email       | [Email issues][#EmailI]            |
| Sprout Redirects   | [Redirect issues][#RedirectsI]     |
| Sprout Sitemaps    | [Sitemap issues][#SitemapsI]       |

[#FormsI]: https://github.com/barrelstrength/craft-sprout-forms/issues

[#SeoI]: https://github.com/barrelstrength/craft-sprout-seo/issues

[#EmailI]: https://github.com/barrelstrength/craft-sprout-email/issues

[#RedirectsI]: https://github.com/barrelstrength/craft-sprout-redirects/issues

[#SitemapsI]: https://github.com/barrelstrength/craft-sprout-sitemaps/issues

[#DataStudioI]: https://github.com/barrelstrength/craft-sprout-data-studio/issues

## Changelog

Changes are tracked per module.

| Module              | Changelog                                      |
|:--------------------|:-----------------------------------------------|
| Core                | [View](./CHANGELOG/CHANGELOG-CORE.md)          |
| Data Studio         | [View](./CHANGELOG/CHANGELOG-DATA-STUDIO.md)   |
| Forms               | [View](./CHANGELOG/CHANGELOG-FORMS.md)         |
| Mailer              | [View](./CHANGELOG/CHANGELOG-MAILER.md)        |
| Metadata            | [View](./CHANGELOG/CHANGELOG-META.md)          |
| Redirects           | [View](./CHANGELOG/CHANGELOG-REDIRECTS.md)     |
| Sent Email          | [View](./CHANGELOG/CHANGELOG-SENT-EMAIL.md)    |
| Sitemaps            | [View](./CHANGELOG/CHANGELOG-SITEMAPS.md)      |
| Transactional Email | [View](./CHANGELOG/CHANGELOG-TRANSACTIONAL.md) |
| URIs                | [View](./CHANGELOG/CHANGELOG-URIS.md)          |

## Usage

Include Sprout modules in a Sprout Plugin composer.json file:

``` json
{
  "require": {
    "barrelstrength/sprout": "^4.44.444"
  }
}
```
