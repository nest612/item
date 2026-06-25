# Publish ItemsPlus on Poggit

The repository is already prepared for Poggit-CI through `.poggit.yml`.

## 1. Create the GitHub repository

Create a **public** GitHub repository named `ItemsPlus` and use `main` as the default branch.

Upload the contents of this folder directly to the repository root. The repository root must contain `plugin.yml`, `.poggit.yml`, `README.md`, `LICENSE`, `resources/` and `src/`.

## 2. Recommended GitHub information

**Repository description**

```text
Create and manage custom items, tools, armor, blocks and ores in-game on PocketMine-MP 5.
```

**Recommended topics**

```text
pocketmine pocketmine-mp pmmp minecraft-bedrock custom-items custom-blocks custom-ores customies php
```

## 3. Enable Poggit-CI

1. Sign in to Poggit with the GitHub account that owns the repository.
2. Open the Poggit-CI administration page.
3. Enable CI for the `ItemsPlus` repository.
4. Push a commit to the `main` branch.
5. Open the ItemsPlus project page in Poggit-CI and confirm that a development `.phar` was built.

## 4. Submit the release

1. Open the successful ItemsPlus development build.
2. Select the release option.
3. Use version `2.4.5`.
4. Paste the contents of `POGGIT_RELEASE_DESCRIPTION.md` into the release description.
5. Select categories related to developer tools, mechanics and blocks/items when available.
6. Submit the release for review.

## 5. Before submission

- Confirm that the repository is public.
- Confirm that the source matches the submitted build.
- Confirm that `LICENSE` is present.
- Confirm that Customies is listed as a required dependency.
- Confirm that no `.phar`, server data, logs, credentials or private resource packs are committed.
- Test the development build on PocketMine-MP 5 with Customies.
