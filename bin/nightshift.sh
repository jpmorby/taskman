#!/usr/bin/env bash

set -e

if [ -n "$(git status --porcelain)" ]; then
    echo "Working directory is dirty.  Commit or stash changes first."
    exit 1
fi

ITERATIONS=${1:-10}

BRANCH="overnight-batch-$(date +%Y%m%d-%H%M%S)"

export CLAUDE_CODE_TASK_LIST_ID="jarvis-$(date +%Y%m%d)"

git checkout main
git pull origin main
git checkout -b "$BRANCH"

echo "Working on branch: $BRANCH"

echo "Task list: $CLAUDE_CODE_TASK_LIST_ID"

for ((i=1; i< ITERATIONS; i++)); do
echo "== Iteration $i of $ITERATIONS =="

claude --dangerously-skip-permissions  "$(cat << 'EOF'
1. Use the gitlab issues skill to fetch all open issues from this repository.
2. Check your tasks.  Filter out any issues that already have a task (completed or in_progress).
3. If no issues remain after filtering, create `.jarvis-complete` and exit.
4. From the remaining issues, choose ONE that seems most appropriate to work on next.
5. Create a Task for this issue and mark it `in_progress`.
6. Implement the fix or feature.
7. Write tests if applicable.  Make them pass.
8. Run `composer run format:dirty` to format code.
9. Run `vendor/bin/pest --parallel` to confirm the full test suite passes.
10. If tests fail, attempt to fix.  After 3 failed attemps, mark the task `stuck`, create `.jarvis-stuck` containing the issue number and 
11. Commit with message format: `ISSUE: #<issue-number>: <short-description>`.
12. Mark the Task as `completed`.
EOF
)"

# Check for stuck state
if [ -f .jarvis-stuck ]; then
    echo "Agent got stuck on iteration: $i:"
    cat .jarvis-stuck
    rm -f .jarvis-stuck
    exit 1
fi

git push -u origin "$BRANCH"

# Check for completion
if [ -f .jarvis-complete ]; then
    echo "All issues complete after $i iterations."
    rm -f .jarvis-complete
    echo "Review with: git log main..$BRANCH"
    exit 0
fi

done

echo "Reached max iterations ($ITERATIONS). Review with git log main..$BRANCH"
