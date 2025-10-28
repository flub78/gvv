# Claude Code Agent Definitions for GVV Project

This directory contains detailed agent definitions optimized for the GVV (Gestion Vol à voile) project. Each agent is specialized for specific tasks and has tailored instructions based on the project's technology stack (CodeIgniter 2.x, PHP 7.4, MySQL, Bootstrap 5).

## Available Agents

1. [Software Architect](software_architect.md) - Design system architecture, plan features, and ensure architectural consistency
2. [Code Developer](code_developer.md) - Implement features following GVV patterns and conventions
3. [Code Reviewer](code_reviewer.md) - Review code quality, identify issues, and ensure standards compliance
4. [Security Auditor](security_auditor.md) - Identify and fix security vulnerabilities
5. [Database Expert](database_expert.md) - Design, optimize, and maintain database schema and queries
6. [Technical Writer](technical_writer.md) - Create and maintain technical documentation
7. [Test Writer](test_writer.md) - Write comprehensive tests for GVV components
8. [Performance Analyzer](performance_analyzer.md) - Identify and fix performance bottlenecks
9. [Log Analyzer](log_analyzer.md) - Analyze logs to identify issues, patterns, and security concerns
10. [GUI Expert](gui_expert.md) - Design and improve user interfaces with Bootstrap 5
11. [Troubleshooter](troubleshooter.md) - Diagnose and fix bugs and issues quickly

## How to Use These Agents

### Creating an Agent in Claude Code

1. **Access Agent Settings**
   - Open Claude Code settings
   - Navigate to "Agents" section

2. **Create New Agent**
   - Click "Create New Agent"
   - Enter agent name (e.g., "GVV Code Developer")
   - Open the corresponding agent file from this directory
   - Copy the entire content from the "Agent Instructions" section
   - Paste into Claude Code agent configuration
   - Save agent

3. **Invoke Agent**
   - Use `/agent [agent-name]` command
   - Or select from agent dropdown
   - Provide specific task details

### Best Practices

1. **Be Specific**: Provide clear, detailed task descriptions
2. **Context**: Reference relevant files, line numbers, error messages
3. **Scope**: Keep tasks focused and manageable
4. **Review**: Always review agent output before applying changes
5. **Test**: Run tests after implementing agent suggestions

### Example Usage

```markdown
@code-developer
Task: Implement member search functionality

Requirements:
- Add search by name, email, license number
- Display results in paginated table
- Use Bootstrap 5 for UI
- Multi-language support (FR/EN/NL)
- Use metadata system for form/table generation

Files to modify:
- application/controllers/membres.php
- application/models/membres_model.php
- application/views/membres/search.php

Please:
1. Add search method to controller
2. Implement search query in model
3. Create search view with Bootstrap form
4. Add language keys for FR/EN/NL
5. Write PHPUnit tests
```

## Agent Characteristics

Each agent definition includes:
- **Purpose**: Clear role definition
- **Responsibilities**: Specific duties
- **Agent Instructions**: Complete prompt ready to paste into Claude Code
- **GVV-Specific Knowledge**: Project patterns, constraints, conventions
- **Code Examples**: Real templates for common tasks
- **Commands**: Bash/SQL commands for the agent's domain
- **Best Practices**: Do's and don'ts
- **Templates**: Reports, checklists, documentation formats
- **Anti-Patterns**: Common mistakes to avoid

## Maintenance

These agent definitions should be updated when:
- New patterns are established
- Common issues are discovered
- Tools or processes change
- Framework versions update
- Best practices evolve

**Last Updated:** 2025-01-28
**Maintained By:** GVV Development Team
**Version:** 1.0
