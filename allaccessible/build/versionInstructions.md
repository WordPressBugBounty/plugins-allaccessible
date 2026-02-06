
The version bump system centralizes version number in one place (constants.php) and provides tools to automatically update all references when you're ready to release a new version. This eliminates the manual work of finding and updating version numbers in multiple files.

## Step by step plan for running the version bump

1. **Using the command line script (recommended for local development)**:
    - Navigate to the plugin's directory in your terminal
    - Run: `php build/version-bump.php 1.3.7 1.3.8` (replacing with your current and new version numbers)
    - Review the changes in constants.php, allaccessible.php, and README.txt
    - Update the changelog entry that was automatically added to README.txt

[//]: # (2. **Using the GitHub workflow &#40;recommended for team environments&#41;**:)

[//]: # (    - Go to your GitHub repository)

[//]: # (    - Navigate to the "Actions" tab)

[//]: # (    - Select the "Version Bump" workflow)

[//]: # (    - Click "Run workflow")

[//]: # (    - Enter your current version &#40;e.g., 1.3.7&#41; and new version &#40;e.g., 1.3.8&#41;)

[//]: # (    - GitHub will create a new branch and pull request with all the version changes)

[//]: # (    - Review the PR, update the changelog, and merge when ready)

2. **After running the version bump**:
    - Test your plugin with the new version to ensure everything works
    - Create a new tag in your repository for the release
    - If using SVN for WordPress.org, update your SVN repository with the new version

[//]: # (The GitHub workflow option is particularly useful as it creates a pull request that your team can review before finalizing the version change.)
