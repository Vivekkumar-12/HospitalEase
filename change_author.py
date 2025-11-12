def commit_callback(commit):
    # Set author and committer name/email for every commit
    commit.author_name = b"Vivek Kumar"
    commit.author_email = b"randhirkumar1411@gmail.com"
    commit.committer_name = b"Vivek Kumar"
    commit.committer_email = b"randhirkumar1411@gmail.com"
