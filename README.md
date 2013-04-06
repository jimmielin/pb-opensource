pb-opensource
=============

PandaBoards Shared-Source Initiative
-------------

This is the repository for the **PandaBoards** (http://pandaboards.com) Developers to publish portions of the source code, documentation of internal APIs and modified versions of other Open-Source Projects that have been used (according to their respective licenses).

**PandaBoards**-specific source-code is published under the MIT License.

Any modified open-source code is licensed under their original licenses.

What is this for?
-------------

The **PandaBoards** team is planning to release a public API for modification development, to allow our users to better extend their forums' features.
This source code is provided for a reference to our internal code (sometimes merely method definitions are retained).

Your security sucks!
-------------

**PandaBoards** was developed to be secure, but coffee sometimes does no good to that (not to mention 20K+ sloc in two weeks). If any routines within the shared code is found to be insecure, please do report it to us! You wouldn't take that to your advantage, would you? :)

We do not believe in security through obscurity, therefore we are opening up a major part of our security-related processing code (with minor changes, for example hiding our crypto key) for public viewing.

Will this code run a full copy of PandaBoards, offline?
-------------

This code is nowhere near complete - it's just a portion of the full PB source (> 20k SLoC) that is run live.

We encourage any developers to discover potential issues or develop enhancements within this source.

Who to contact
-------------

We are open to providing developers with appropriate documentation on **PandaBoards**, **BoltEngine** (The backend framework, derived from CakePHP) and other components if they are interested in developing for our platform.

Contact us at http://support.pandaboards.com or the owner of this repository, Jimmie Lin.
