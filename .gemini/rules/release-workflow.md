# Woo Style+ Release & Push Workflow

Whenever changes, enhancements, or bug fixes are made to this codebase:
1. **Bump the Plugin Version**:
   - In `woo-style-plus.php` (header `Version: X.X.X` and `define( 'WSP_VERSION', 'X.X.X' );`).
   - In `readme.txt` (`Stable tag: X.X.X`).
2. **Commit and Push**:
   - `git add .`
   - `git commit -m "Bump version to vX.X.X: [Summary of changes]"`
   - `git tag -a vX.X.X -m "Woo Style+ vX.X.X"`
   - `git push origin main --tags`
3. **Publish GitHub Release**:
   - Publish the corresponding release tag on `https://github.com/Emprordan/woo-style-` so the 1-click update system immediately detects it for all users.
4. **Update Zip Archive**:
   - Repackage `Woo styles+.zip` in the parent directory.
