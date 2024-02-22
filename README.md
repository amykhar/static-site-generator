# A Static Site Generator for my Obsidian Vault
**February 22, 2024**

This little project can be configured to read a directory of Markdown files, convert them to HTML, copy over any images into an assets folder, and then deploy the site to an S3 bucket. (Right now, only the parsing part works, and not completely)

## Fair Warning
This is not user-friendly code.  It's not something you can just download and use.  It may never be.  Instead, it's something you can take and hack up to make it suit your needs.

## To Use
If you have PHP 8.3 on your system, it's a simple matter to clone (or fork) this repo, create a .env.local file with your configuration settings, and then run:

```bash
bin\console parse-markdown
```

Another option is to use:
```bash
composer parse
```

Right now, the code only does some rudimentary Markdown parsing, and it takes some Markdown syntax and warps it for my own needs.  
