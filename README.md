# 📦 S3 Index Website

A simple and customizable web interface for browsing and indexing public files and folders hosted on an S3-compatible object storage.

---
## ✨ Features

- 🗂 Automatically indexes all files and folders from a configured S3 bucket.
- 🌐 Web-based file browser — visitors can view and explore folders like a directory tree.
- 🚫 **Privacy by convention:** Any folder prefixed with `PRIVATE_` will be **hidden** from the listing (e.g., `PRIVATE_docs/` or `PRIVATE_BACKUPS/`).
- 🔄 Fast and lightweight, ideal for static hosting.
- 🎨 Customizable theme, layout, and icons.

---
## 📁 Folder Visibility Rules
```
| Folder Name          | Visible on Website | Notes                         |
|______________________|____________________|_______________________________|
| docs/                | ✅ Yes             | Public folder                 |
| PRIVATE_docs/        | ❌ No              | Automatically hidden          |
| images/              | ✅ Yes             | All files shown               |
| PRIVATE_BACKUPS/     | ❌ No              | Hidden by prefix              |
```

---

## Authors

- [@NekoMonci12](https://github.com/NekoMonci12)

