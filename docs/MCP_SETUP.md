# MCP Server Setup for Claude Desktop

Model Context Protocol (MCP) servers enable Claude Desktop to interact with your local filesystem, GitHub, Git, and more.

**Version:** 1.0.0
**Date:** 2025-10-31
**For:** Claude Desktop (not LM Studio)

## Installation

### 1. Claude Desktop MCP Configuration

**Config File Location (Windows):**
```
%APPDATA%\Claude\claude_desktop_config.json
```

**Config File Location (Mac/Linux):**
```
~/.config/claude/claude_desktop_config.json
```

### 2. MCP Servers Configured

✅ **filesystem** - Read/write files in the repository
✅ **github** - GitHub API access (issues, PRs, repos)
✅ **git** - Git operations (log, diff, blame)
✅ **memory** - Persistent memory across sessions

### 3. Setup GitHub Token

1. Go to https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Select scopes:
   - `repo` (Full control of private repositories)
   - `read:org` (Read org and team membership)
   - `user:email` (Access user email addresses)
4. Generate token
5. Copy token
6. Edit `claude_desktop_config.json`:
   ```json
   "GITHUB_PERSONAL_ACCESS_TOKEN": "ghp_your_token_here"
   ```

### 4. Restart Claude Desktop

After editing `claude_desktop_config.json`:
1. Close Claude Desktop completely
2. Reopen Claude Desktop
3. MCP servers will load automatically

## Verification

### Check MCP Servers Loaded

Ask Claude Desktop:
```
Can you list the available MCP tools?
```

You should see tools from:
- filesystem (read_file, write_file, list_directory, etc.)
- github (create_issue, get_issue, list_issues, etc.)
- git (git_log, git_diff, git_status, etc.)
- memory (store_memory, recall_memory, etc.)

### Test Filesystem Access

```
Can you read the file admin/config.php?
```

### Test GitHub Access

```
Can you list the recent issues in this repository?
```

### Test Git Access

```
Can you show me the git log for the last 5 commits?
```

## Usage Examples

### Reading Files
```
Read admin/user_management.php and explain how the multi-role system works
```

### Creating GitHub Issues
```
Create a GitHub issue titled "Implement user session timeout" with label "enhancement"
```

### Git Operations
```
Show me the diff between the last two commits
```

### Persistent Memory
```
Remember that the production server uses PHP 8.1 and the staging server uses PHP 8.2
```

## MCP Server Capabilities

### 1. Filesystem Server

**Available Tools:**
- `read_file` - Read file contents
- `write_file` - Write to files
- `list_directory` - List directory contents
- `create_directory` - Create directories
- `move_file` - Move/rename files
- `search_files` - Search for files

**Use Cases:**
- Read code files for review
- Edit configuration files
- Create new files
- Reorganize project structure

### 2. GitHub Server

**Available Tools:**
- `create_issue` - Create new issues
- `get_issue` - Get issue details
- `list_issues` - List repository issues
- `update_issue` - Update issue status/labels
- `create_pull_request` - Create PRs
- `list_pull_requests` - List PRs
- `get_file_contents` - Get file from GitHub

**Use Cases:**
- Track bugs and features
- Review open issues
- Create PRs from branches
- Update issue labels

### 3. Git Server

**Available Tools:**
- `git_log` - View commit history
- `git_diff` - View changes between commits
- `git_status` - Check working tree status
- `git_diff_unstaged` - View uncommitted changes
- `git_show` - Show commit details
- `git_commit` - Create commits

**Use Cases:**
- Review commit history
- Analyze code changes
- Check repository status
- Create commits

### 4. Memory Server

**Available Tools:**
- `store_memory` - Save information
- `recall_memory` - Retrieve saved information
- `list_memories` - List all stored memories
- `delete_memory` - Remove memories

**Use Cases:**
- Remember project conventions
- Store frequently used commands
- Track deployment procedures
- Remember API endpoints

## Security Considerations

### Filesystem Access

MCP filesystem server has **full access** to the repository directory. Be careful with:
- Sensitive files (.env, keys, credentials)
- Production data
- Critical configuration files

**Best Practice:** Review changes before confirming file writes.

### GitHub Token

- Store token securely in config file
- Don't commit config file to version control
- Use minimal required scopes
- Rotate tokens periodically

**Token Permissions:**
- `repo` - Needed for private repo access
- `read:org` - Optional, for organization access
- `user:email` - Optional, for user information

### Git Operations

- MCP can create commits
- Changes are local until pushed
- Review git commands before execution
- Verify branch before commits

## Troubleshooting

### MCP Servers Not Loading

**Check:**
1. Config file location correct
2. JSON syntax valid (use https://jsonlint.com/)
3. npx is installed (`npm -v` in terminal)
4. Claude Desktop fully restarted

**View Logs (Windows):**
```
%APPDATA%\Claude\logs\
```

**View Logs (Mac/Linux):**
```
~/.config/claude/logs/
```

### GitHub Token Issues

**Error:** "Authentication failed"

**Solution:**
1. Verify token is valid
2. Check token scopes
3. Ensure no extra spaces in config
4. Try regenerating token

### Filesystem Permission Errors

**Error:** "Access denied"

**Solution:**
1. Check file permissions
2. Run Claude Desktop as user (not admin)
3. Verify path in config is correct

### Git Server Errors

**Error:** "Not a git repository"

**Solution:**
1. Verify repository path in config
2. Ensure path points to git root (contains .git/)
3. Check path format (Windows: backslashes, Unix: forward slashes)

## Comparison: Claude Desktop vs LM Studio

| Feature | Claude Desktop + MCP | LM Studio |
|---------|---------------------|-----------|
| **Filesystem Access** | ✅ Via MCP | ❌ No direct access |
| **GitHub Integration** | ✅ Via MCP | ❌ Manual API calls only |
| **Git Operations** | ✅ Via MCP | ❌ Manual commands only |
| **Memory** | ✅ Persistent across sessions | ❌ No persistence |
| **Local Inference** | ❌ Uses Anthropic API | ✅ Fully local |
| **Code Generation** | ✅ Excellent | ✅ Excellent |
| **Context Window** | 200K tokens | 8K-32K tokens |
| **Cost** | $$ API credits | Free (local) |

**Recommendation:** Use both!
- **Claude Desktop + MCP** for file operations, GitHub, Git
- **LM Studio** for git hooks, quick reviews, offline work

## Advanced Configuration

### Multiple Repositories

```json
{
  "mcpServers": {
    "project1-fs": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-filesystem", "/path/to/project1"]
    },
    "project1-git": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-git", "--repository", "/path/to/project1"]
    },
    "project2-fs": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-filesystem", "/path/to/project2"]
    }
  }
}
```

### Custom MCP Servers

Create your own MCP servers for project-specific needs:

**Example: Database MCP Server**
```json
{
  "mcpServers": {
    "database": {
      "command": "node",
      "args": ["/path/to/custom-db-mcp-server.js"]
    }
  }
}
```

### Environment Variables

```json
{
  "mcpServers": {
    "custom": {
      "command": "npx",
      "args": ["-y", "my-mcp-server"],
      "env": {
        "API_KEY": "secret",
        "DATABASE_URL": "localhost:5432"
      }
    }
  }
}
```

## Resources

**MCP Documentation:**
- Official Docs: https://modelcontextprotocol.io/
- Server List: https://github.com/modelcontextprotocol/servers
- Specification: https://spec.modelcontextprotocol.io/

**Available MCP Servers:**
- filesystem: https://github.com/modelcontextprotocol/servers/tree/main/src/filesystem
- github: https://github.com/modelcontextprotocol/servers/tree/main/src/github
- git: https://github.com/modelcontextprotocol/servers/tree/main/src/git
- memory: https://github.com/modelcontextprotocol/servers/tree/main/src/memory

**Creating Custom Servers:**
- TypeScript SDK: https://github.com/modelcontextprotocol/typescript-sdk
- Python SDK: https://github.com/modelcontextprotocol/python-sdk

---

**Last Updated:** 2025-10-31
**Config File:** `%APPDATA%\Claude\claude_desktop_config.json`
**Repository:** C:\Users\k33bz\OneDrive\git\Server1586-clean
**Status:** ✅ Configured and ready
