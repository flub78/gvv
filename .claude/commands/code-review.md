# Command: code-review

Code review for the files or directories specified in $ARGUMENTS

## Steps
1. Read the files at path: $ARGUMENTS
2. Identify bugs, potential bugs, inefficient implementation, dead code, poor style, high complexity and code duplication
3. Generate or update a markdown document into doc/reviews
4. List the problems found during the analysis
5. Manage a todo list with problem ordered by decreasing criticality to keep track of the mitigations.

 